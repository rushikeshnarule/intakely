<?php
/**
 * Intakely — Database
 * PostgreSQL database wrapper using PDO with prepared statements.
 */

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use PDOException;
use RuntimeException;

final class Database
{
    private ?PDO $pdo = null;
    private string $dsn;
    private string $user;
    private string $password;
    private bool $inTransaction = false;

    public function __construct(
        ?string $host = null,
        ?int $port = null,
        ?string $dbname = null,
        ?string $user = null,
        ?string $password = null,
    ) {
        $dbUrl = env('DATABASE_URL');
        if (!empty($dbUrl)) {
            $parsed = parse_url($dbUrl);
            $host = $parsed['host'] ?? $host;
            $port = isset($parsed['port']) ? (int)$parsed['port'] : $port;
            $dbname = isset($parsed['path']) ? ltrim($parsed['path'], '/') : $dbname;
            $user = $parsed['user'] ?? $user;
            $password = $parsed['pass'] ?? $password;
        }

        $host = $host ?? env('DB_HOST', 'localhost');
        $port = $port ?? (int) env('DB_PORT', '5432');
        $dbname = $dbname ?? env('DB_NAME', 'intakely');
        $user = $user ?? env('DB_USER', 'intakely_app');
        $password = $password ?? env('DB_PASS', '');

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        $sslmode = env('DB_SSLMODE');
        if (!empty($sslmode)) {
            $dsn .= ";sslmode={$sslmode}";
        }

        $this->dsn      = $dsn;
        $this->user     = $user;
        $this->password = $password;
    }

    /**
     * Get the PDO connection (lazy).
     */
    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            try {
                $this->pdo = new PDO($this->dsn, $this->user, $this->password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_STRINGIFY_FETCHES  => false,
                ]);
            } catch (PDOException $e) {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
            }
        }

        return $this->pdo;
    }

    /**
     * Execute a prepared statement and return results.
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        if (!empty($params)) {
            // Handle $1, $2, ... Postgres positional placeholders
            if (array_is_list($params) && preg_match('/\$[1-9]/', $sql)) {
                $namedParams = [];
                foreach ($params as $idx => $val) {
                    $namedParams['p' . ($idx + 1)] = is_bool($val) ? ($val ? 'true' : 'false') : $val;
                }
                $sql = preg_replace_callback('/\$([1-9][0-9]*)/', function($m) {
                    return ':p' . $m[1];
                }, $sql);
                $params = $namedParams;
            } else {
                foreach ($params as $k => $v) {
                    if (is_bool($v)) {
                        $params[$k] = $v ? 'true' : 'false';
                    }
                }
            }
        }

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all rows.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single row.
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    /**
     * Fetch a single column value.
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * Execute a statement (INSERT/UPDATE/DELETE) and return rows affected.
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(): void
    {
        if (!$this->inTransaction) {
            $this->pdo()->beginTransaction();
            $this->inTransaction = true;
        }
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): void
    {
        if ($this->inTransaction) {
            $this->pdo()->commit();
            $this->inTransaction = false;
        }
    }

    /**
     * Roll back the current transaction.
     */
    public function rollBack(): void
    {
        if ($this->inTransaction) {
            $this->pdo()->rollBack();
            $this->inTransaction = false;
        }
    }

    /**
     * Check if inside a transaction.
     */
    public function isInTransaction(): bool
    {
        return $this->inTransaction;
    }

    /**
     * Set a local session variable (for RLS).
     */
    public function setLocal(string $key, string $value): void
    {
        // SET LOCAL is only valid inside a transaction
        $this->pdo()->exec("SET LOCAL {$key} = " . $this->pdo()->quote($value));
    }
}
