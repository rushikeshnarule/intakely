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

// Run middleware pipeline
$pipeline = new MiddlewarePipeline($container);
$response = $pipeline->handle($request, function (Request $request) use ($container) {
    $router = $container->get(Router::class);
    return $router->dispatch($request);
});

$response->send();
