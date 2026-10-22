<?php
/**
 * Intakely — Base Model
 *
 * Active-record-style base class for PostgreSQL tables with UUID primary keys,
 * JSONB casting, and automatic tenant scoping via the Database layer.
 */

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

abstract class Model
{
    // ── Subclass Configuration ──────────────────────────────────

    /** Table name (override in subclass). */
    protected string $table = '';

    /** Mass-assignable columns (override in subclass). */
    protected array $fillable = [];

    /**
     * Column casts.
     *
     * Supported: 'json', 'bool', 'int', 'float', 'datetime'
     *
     * @var array<string, string>
     */
    protected array $casts = [];

    // ── Instance State ──────────────────────────────────────────

    /** @var array<string, mixed> */
    protected array $attributes = [];

    /** Whether this model instance exists in the database. */
    protected bool $exists = false;

    // ── Constructor ─────────────────────────────────────────────

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    // ── Static Finders ──────────────────────────────────────────

    /**
     * Find a row by its UUID primary key.
     */
    public static function find(string $id): ?static
    {
        $instance = new static();
        $sql = sprintf(
            'SELECT * FROM %s WHERE "id" = :id LIMIT 1',
            self::quoteTable($instance->table),
        );

        $row = static::db()->fetch($sql, ['id' => $id]);

        if ($row === null) {
            return null;
        }

        return $instance->hydrate($row);
    }

    /**
     * Fetch all rows from the table.
     *
     * @return list<static>
     */
    public static function all(): array
    {
        $instance = new static();
        $sql = sprintf('SELECT * FROM %s ORDER BY "created_at" DESC', self::quoteTable($instance->table));

        $rows = static::db()->fetchAll($sql);
        return array_map(fn(array $row) => (new static())->hydrate($row), $rows);
    }

    /**
     * Query with a simple WHERE clause.
     *
     * @param array<string, mixed> $conditions  Column => value pairs
     * @param string               $orderBy     ORDER BY clause (e.g. "created_at DESC")
     * @param int|null             $limit
     * @return list<static>
     */
    public static function where(array $conditions, string $orderBy = 'created_at DESC', ?int $limit = null): array
    {
        $instance = new static();

        $whereClauses = [];
        $params       = [];
        foreach ($conditions as $col => $value) {
            if ($value === null) {
                $whereClauses[] = self::quoteCol($col) . ' IS NULL';
            } else {
                $whereClauses[] = self::quoteCol($col) . " = :{$col}";
                $params[$col]   = $value;
            }
        }

        $sql = sprintf(
            'SELECT * FROM %s WHERE %s ORDER BY %s',
            self::quoteTable($instance->table),
            implode(' AND ', $whereClauses),
            $orderBy,
        );

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        $rows = static::db()->fetchAll($sql, $params);
        return array_map(fn(array $row) => (new static())->hydrate($row), $rows);
    }

    /**
     * Create a new row.
     *
     * @param array<string, mixed> $data
     */
    public static function create(array $data): static
    {
        $instance = new static();
        $instance->fill($data);

        // Auto-generate UUID if not provided
        if (empty($instance->attributes['id'])) {
            $instance->attributes['id'] = self::generateUuid();
        }

        // Timestamps
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s.uP');
        $instance->attributes['created_at'] ??= $now;
        $instance->attributes['updated_at'] ??= $now;

        // Prepare insert data: only fillable + id + timestamps
        $insertData = [];
        $allowed = array_merge($instance->fillable, ['id', 'created_at', 'updated_at', 'tenant_id']);

        foreach ($instance->attributes as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $insertData[$key] = $instance->castForDatabase($key, $value);
            }
        }

        $row = static::db()->insert($instance->table, $insertData);
        return (new static())->hydrate($row);
    }

    // ── Instance Methods ────────────────────────────────────────

    /**
     * Save the current instance (UPDATE).
     */
    public function save(): bool
    {
        if (!$this->exists) {
            throw new RuntimeException('Cannot save a model that does not exist. Use create() instead.');
        }

        $data = [];
        foreach ($this->fillable as $col) {
            if (array_key_exists($col, $this->attributes)) {
                $data[$col] = $this->castForDatabase($col, $this->attributes[$col]);
            }
        }

        // Update timestamp
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s.uP');
        $data['updated_at'] = $now;
        $this->attributes['updated_at'] = $now;

        $affected = static::db()->update($this->table, $data, ['id' => $this->attributes['id']]);
        return $affected > 0;
    }

    /**
     * Delete the current instance.
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $affected = static::db()->delete($this->table, ['id' => $this->attributes['id']]);
        $this->exists = false;
        return $affected > 0;
    }

    /**
     * Convert the model to an array, applying casts for output.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->attributes as $key => $value) {
            $result[$key] = $this->castFromDatabase($key, $value);
        }
        return $result;
    }

    // ── Property Access ─────────────────────────────────────────

    public function __get(string $name): mixed
    {
        return $this->castFromDatabase($name, $this->attributes[$name] ?? null);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    // ── Internals ───────────────────────────────────────────────

    /**
     * Fill attributes from an array, filtering to fillable columns.
     *
     * @param array<string, mixed> $data
     */
    protected function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            // Allow fillable columns + system columns
            if (in_array($key, $this->fillable, true)
                || in_array($key, ['id', 'tenant_id', 'created_at', 'updated_at'], true)
            ) {
                $this->attributes[$key] = $value;
            }
        }
    }

    /**
     * Hydrate a model from a raw database row.
     *
     * @param array<string, mixed> $row
     */
    protected function hydrate(array $row): static
    {
        $this->attributes = $row;
        $this->exists = true;
        return $this;
    }

    /**
     * Cast a value for writing to the database.
     */
    private function castForDatabase(string $key, mixed $value): mixed
    {
        $cast = $this->casts[$key] ?? null;

        return match ($cast) {
            'json'     => is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'bool'     => $value ? 'true' : 'false',
            'int'      => (int) $value,
            'float'    => (float) $value,
            default    => $value,
        };
    }

    /**
     * Cast a value after reading from the database.
     */
    private function castFromDatabase(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $cast = $this->casts[$key] ?? null;

        return match ($cast) {
            'json'     => is_string($value) ? json_decode($value, true, 64, JSON_THROW_ON_ERROR) : $value,
            'bool'     => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int'      => (int) $value,
            'float'    => (float) $value,
            'datetime' => $value, // Keep as string; consumers can parse
            default    => $value,
        };
    }

    /**
     * Get the Database instance from the container.
     */
    protected static function db(): Database
    {
        return Container::getInstance()->get(Database::class);
    }

    /**
     * Generate a UUID v4.
     */
    private static function generateUuid(): string
    {
        $bytes = random_bytes(16);

        // Set version to 4
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        // Set variant to RFC 4122
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Quote a table name.
     */
    private static function quoteTable(string $table): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_.]/', '', $table);
        return '"' . str_replace('.', '"."', $safe) . '"';
    }

    /**
     * Quote a column name.
     */
    private static function quoteCol(string $col): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
        return '"' . $safe . '"';
    }
}
