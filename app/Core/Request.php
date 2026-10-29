<?php
/**
 * Intakely — Request
 * Wraps the incoming HTTP request with a clean API.
 */

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $query;
    private array $post;
    private array $server;
    private array $headers;
    private array $cookies;
    private array $files;
    private array $attributes = [];
    private ?string $rawBody = null;

    public function __construct(
        string $method,
        string $uri,
        array $query = [],
        array $post = [],
        array $server = [],
        array $headers = [],
        array $cookies = [],
        array $files = [],
    ) {
        $this->method  = strtoupper($method);
        $this->uri     = $uri;
        $this->path    = parse_url($uri, PHP_URL_PATH) ?: '/';
        $this->query   = $query;
        $this->post    = $post;
        $this->server  = $server;
        $this->headers = $headers;
        $this->cookies = $cookies;
        $this->files   = $files;
    }

    /**
     * Capture the current request from PHP superglobals.
     */
    public static function capture(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        // Parse headers from $_SERVER
        $headers = [];
        if (function_exists('getallheaders')) {
            $all = getallheaders();
            if (is_array($all)) {
                foreach ($all as $k => $v) {
                    $headers[strtolower((string)$k)] = (string)$v;
                }
            }
        }
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($name)] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
        }

        $request = new self(
            method:  $method,
            uri:     $uri,
            query:   $_GET,
            post:    $_POST,
            server:  $_SERVER,
            headers: $headers,
            cookies: $_COOKIE,
            files:   $_FILES,
        );

        return $request;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function allQuery(): array
    {
        return $this->query;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $json = $this->json();
        if (array_key_exists($key, $json)) {
            return $json[$key];
        }
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        $json = $this->json();
        if (array_key_exists($key, $json)) {
            return true;
        }
        return array_key_exists($key, $this->post) || array_key_exists($key, $this->query);
    }

    public function allInput(): array
    {
        if ($this->isJson()) {
            return array_merge($this->query, $this->post, $this->json());
        }
        return array_merge($this->query, $this->post);
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function allPost(): array
    {
        return $this->post;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function allHeaders(): array
    {
        return $this->headers;
    }

    public function cookie(string $name, mixed $default = null): mixed
    {
        return $this->cookies[$name] ?? $default;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function rawBody(): string
    {
        if ($this->rawBody === null) {
            $this->rawBody = file_get_contents('php://input') ?: '';
        }
        return $this->rawBody;
    }

    public function json(): array
    {
        $decoded = json_decode($this->rawBody(), true);
        return is_array($decoded) ? $decoded : [];
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    public function isApi(): bool
    {
        return str_starts_with($this->path, '/api/');
    }

    public function isJson(): bool
    {
        $ct = $this->header('content-type', '');
        return str_contains($ct, 'application/json');
    }

    /**
     * Get the host from the request (for subdomain resolution).
     */
    public function host(): string
    {
        return $this->header('host', $this->server('SERVER_NAME', 'localhost'));
    }

    // --- Attributes (middleware-injected data) ---

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function allAttributes(): array
    {
        return $this->attributes;
    }
}
