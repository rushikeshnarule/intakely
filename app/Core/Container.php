<?php
/**
 * Intakely — IoC Container
 * Minimal service container with singleton support.
 */

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Container
{
    private static ?self $instance = null;

    /** @var array<string, callable> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a shared (singleton) binding.
     */
    public function singleton(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Store an already-resolved instance.
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve a binding.
     */
    public function get(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $this->instances[$abstract] = ($this->bindings[$abstract])($this);
            return $this->instances[$abstract];
        }

        // Try auto-resolve if class exists
        if (class_exists($abstract)) {
            $this->instances[$abstract] = new $abstract();
            return $this->instances[$abstract];
        }

        throw new RuntimeException("No binding registered for [{$abstract}].");
    }

    /**
     * Check if a binding exists.
     */
    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || isset($this->bindings[$abstract]);
    }

    /**
     * Create a new instance (non-singleton).
     */
    public function make(string $abstract, array $params = []): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        if (class_exists($abstract)) {
            return new $abstract(...$params);
        }

        throw new RuntimeException("Cannot make [{$abstract}].");
    }
}
