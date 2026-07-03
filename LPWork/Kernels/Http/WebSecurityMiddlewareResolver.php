<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Foundation\Application;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Middleware\SessionMiddleware;
use LPWork\Routing\RouteMatch;
use LPWork\Security\Csrf\CsrfMiddleware;
use LPWork\Security\SecurityConfig;

/**
 * Resolves web security middleware resolver values into runtime objects.
 */
final readonly class WebSecurityMiddlewareResolver
{
    /**
     * Creates a new WebSecurityMiddlewareResolver instance.
     */
    public function __construct(private Application $app) {}

    /**
     * @return list<Middleware>
     */
    public function resolve(RouteMatch $match): array
    {
        if ($match->route()->isApi()) {
            return [];
        }

        $security = $this->securityConfig();

        if ($security === null || !$security->csrf()->enabled()) {
            return [];
        }

        return [
            $this->middleware(SessionMiddleware::class),
            $this->middleware(CsrfMiddleware::class),
        ];
    }

    /**
     * @param class-string<Middleware> $class
     */
    private function middleware(string $class): Middleware
    {
        $middleware = $this->app->container()->make($class);

        if (!$middleware instanceof Middleware) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject($class);
        }

        return $middleware;
    }

    private function securityConfig(): ?SecurityConfig
    {
        try {
            $security = $this->app->container()->make(SecurityConfig::class);
        } catch (CannotResolveDependencyException) {
            return null;
        }

        return $security instanceof SecurityConfig ? $security : null;
    }
}
