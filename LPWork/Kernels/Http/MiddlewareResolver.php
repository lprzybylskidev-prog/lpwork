<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use LPWork\Foundation\Application;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Middleware\Exceptions\InvalidMiddlewareException;
use LPWork\Middleware\Exceptions\MiddlewareNotFoundException;
use LPWork\Routing\RouteMatch;
use LPWork\Routing\Router;

/**
 * Resolves middleware resolver values into runtime objects.
 */
final readonly class MiddlewareResolver
{
    /**
     * Creates a new MiddlewareResolver instance.
     */
    public function __construct(
        private Application $app,
    ) {}

    /**
     * @return list<Middleware>
     */
    public function resolve(RouteMatch $match): array
    {
        return $this->middlewareList($match->route()->middlewareList());
    }

    /**
     * @return list<Middleware>
     */
    public function resolveGlobal(Router $router): array
    {
        return $this->middlewareList($router->globalMiddlewareList());
    }

    /**
     * @param list<string> $middlewareClasses
     *
     * @return list<Middleware>
     */
    private function middlewareList(array $middlewareClasses): array
    {
        $middleware = [];

        foreach ($middlewareClasses as $middlewareClass) {
            $middleware[] = $this->middleware($middlewareClass);
        }

        return $middleware;
    }

    private function middleware(string $middleware): Middleware
    {
        if (!class_exists($middleware)) {
            throw new MiddlewareNotFoundException($middleware);
        }

        if (!is_a($middleware, Middleware::class, true)) {
            throw new InvalidMiddlewareException($middleware);
        }

        $instance = $this->app->container()->make($middleware);

        if (!$instance instanceof Middleware) {
            throw new InvalidMiddlewareException($middleware);
        }

        return $instance;
    }
}
