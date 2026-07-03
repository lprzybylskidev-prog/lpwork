<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http;

use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Events\EventDispatcher;
use LPWork\Foundation\Application;
use LPWork\Middleware\Contracts\Middleware;
use LPWork\Routing\RouteMatch;
use LPWork\Throttle\HttpThrottleMiddleware;
use LPWork\Throttle\ThrottleConfig;
use LPWork\Throttle\ThrottleLimiter;

/**
 * Resolves http throttle middleware resolver values into runtime objects.
 */
final readonly class HttpThrottleMiddlewareResolver
{
    /**
     * Creates a new HttpThrottleMiddlewareResolver instance.
     */
    public function __construct(private Application $app) {}

    /**
     * @return list<Middleware>
     */
    public function resolve(RouteMatch $match): array
    {
        $config = $this->config();
        $limiter = $this->limiter();

        if ($config === null || $limiter === null) {
            return [];
        }

        $flow = $match->route()->isApi() ? 'api' : 'web';
        $policy = $config->policy($match->route()->isApi() ? 'http_api' : 'http_web');

        return [new HttpThrottleMiddleware($limiter, $policy, $flow, $this->events())];
    }

    private function config(): ?ThrottleConfig
    {
        try {
            $config = $this->app->container()->make(ThrottleConfig::class);
        } catch (CannotResolveDependencyException) {
            return null;
        }

        return $config instanceof ThrottleConfig ? $config : null;
    }

    private function limiter(): ?ThrottleLimiter
    {
        try {
            $limiter = $this->app->container()->make(ThrottleLimiter::class);
        } catch (CannotResolveDependencyException) {
            return null;
        }

        return $limiter instanceof ThrottleLimiter ? $limiter : null;
    }

    private function events(): ?EventDispatcher
    {
        try {
            $dispatcher = $this->app->container()->make(EventDispatcher::class);
        } catch (CannotResolveDependencyException) {
            return null;
        }

        return $dispatcher instanceof EventDispatcher ? $dispatcher : null;
    }
}
