<?php
/**
 * Intakely — Middleware Pipeline
 * Executes the global middleware stack in order, then the final handler.
 */

declare(strict_types=1);

namespace App\Core;

final class MiddlewarePipeline
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Run the request through the middleware stack defined in config.
     */
    public function handle(Request $request, callable $finalHandler): Response
    {
        $middlewareClasses = config('app.middleware', []);

        // Build the pipeline from inside-out (last middleware wraps closest to handler)
        $pipeline = $finalHandler;

        foreach (array_reverse($middlewareClasses) as $middlewareClass) {
            $pipeline = function (Request $req) use ($middlewareClass, $pipeline): Response {
                /** @var object $middleware */
                $middleware = $this->container->has($middlewareClass)
                    ? $this->container->get($middlewareClass)
                    : new $middlewareClass();

                return $middleware->handle($req, $pipeline);
            };
        }

        return $pipeline($request);
    }
}
