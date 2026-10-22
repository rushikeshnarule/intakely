<?php
/**
 * Intakely — Front Controller
 * All requests route through here.
 */

declare(strict_types=1);

// Autoloader
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Router;
use App\Core\Container;
use App\Core\Request;
use App\Core\Response;
use App\Core\MiddlewarePipeline;

$container = Container::getInstance();
$request = Request::capture();
$container->instance(Request::class, $request);

try {
    // Run middleware pipeline
    $pipeline = new MiddlewarePipeline($container);
    $response = $pipeline->handle($request, function (Request $request) use ($container) {
        $router = $container->get(Router::class);
        return $router->dispatch($request);
    });

    $response->send();
} catch (\Throwable $e) {
    error_log((string) $e);
    if (env('APP_DEBUG') === 'true') {
        http_response_code(500);
        echo "<pre style='background:#111;color:#f87171;padding:2rem;font-family:monospace;'>" . e((string) $e) . "</pre>";
    } else {
        http_response_code(500);
        echo "<div style='font-family:sans-serif;padding:3rem;text-align:center;'><h2>An error occurred</h2><p style='color:#666;'>Chambers docket encountered an issue. " . e($e->getMessage()) . "</p></div>";
    }
}
