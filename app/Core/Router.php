<?php
/**
 * Intakely — Router
 * Lightweight URL router with named routes and parameter extraction.
 */

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Router
{
    private Container $container;

    /** @var array<string, array<array{pattern: string, handler: callable|array, middleware: array, name: ?string}>> */
    private array $routes = [];

    /** @var array<string, array{method: string, pattern: string}> */
    private array $namedRoutes = [];

    private string $prefix = '';
    private array $groupMiddleware = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Define a route group with prefix and middleware.
     */
    public function group(array $options, callable $callback): void
    {
        $previousPrefix     = $this->prefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->prefix          .= $options['prefix'] ?? '';
        $this->groupMiddleware  = array_merge(
            $this->groupMiddleware,
            $options['middleware'] ?? []
        );

        $callback($this);

        $this->prefix          = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function get(string $uri, callable|array|string $handler, ?string $name = null): void
    {
        $this->addRoute('GET', $uri, $handler, $name);
    }

    public function post(string $uri, callable|array|string $handler, ?string $name = null): void
    {
        $this->addRoute('POST', $uri, $handler, $name);
    }

    public function put(string $uri, callable|array|string $handler, ?string $name = null): void
    {
        $this->addRoute('PUT', $uri, $handler, $name);
    }

    public function patch(string $uri, callable|array|string $handler, ?string $name = null): void
    {
        $this->addRoute('PATCH', $uri, $handler, $name);
    }

    public function delete(string $uri, callable|array|string $handler, ?string $name = null): void
    {
        $this->addRoute('DELETE', $uri, $handler, $name);
    }

    private function addRoute(string $method, string $uri, callable|array|string $handler, ?string $name): void
    {
        $fullUri = $this->prefix . $uri;
        $pattern = $this->uriToRegex($fullUri);

        $this->routes[$method][] = [
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $this->groupMiddleware,
            'name'       => $name,
        ];

        if ($name !== null) {
            $this->namedRoutes[$name] = ['method' => $method, 'pattern' => $fullUri];
        }
    }

    /**
     * Convert a URI pattern like /users/{id} to a regex.
     */
    private function uriToRegex(string $uri): string
    {
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $uri);
        return '#^' . $regex . '$#';
    }

    /**
     * Dispatch a request to its handler.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path   = $request->path();

        $routeList = $this->routes[$method] ?? [];

        foreach ($routeList as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);
                $request->setAttribute('_route_params', $params);
                foreach ($params as $key => $value) {
                    $request->setAttribute($key, $value);
                }

                return $this->callHandler($route['handler'], $request);
            }
        }

        // 404
        if ($request->isApi()) {
            return Response::json(['error' => 'Not Found'], 404);
        }

        return Response::error('Page not found', 404);
    }

    /**
     * Call a route handler.
     */
    private function callHandler(callable|array|string $handler, Request $request): Response
    {
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            if (!str_starts_with($class, 'App\\')) {
                $class = 'App\\Controllers\\' . $class;
            }
            $controller = $this->container->get($class);
            $result = $controller->$method($request);
        } elseif (is_array($handler)) {
            [$class, $method] = $handler;
            if (is_string($class) && !str_starts_with($class, 'App\\')) {
                $class = 'App\\Controllers\\' . $class;
            }
            $controller = is_string($class) ? $this->container->get($class) : $class;
            $result = $controller->$method($request);
        } else {
            $result = $handler($request);
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html('');
    }

    /**
     * Generate a URL for a named route.
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new RuntimeException("Route [{$name}] not defined.");
        }

        $uri = $this->namedRoutes[$name]['pattern'];
        foreach ($params as $key => $value) {
            $uri = str_replace("{{$key}}", (string) $value, $uri);
        }

        return $uri;
    }
}
