<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\Routing\Router;
use LPWork\Url\UrlGenerator;

/**
 * Represents the routing health check framework component.
 */
final readonly class RoutingHealthCheck implements HealthCheck
{
    /**
     * Creates a new RoutingHealthCheck instance.
     */
    public function __construct(
        private Router $router,
        private UrlGenerator $url,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'routing';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $this->router->routes()->named('health.show');
        $path = $this->url->route('health.show', absolute: false);

        if ($path !== '/health') {
            return HealthCheckResult::unhealthy($this->name(), sprintf('Named health route generated unexpected path [%s].', $path));
        }

        return HealthCheckResult::healthy($this->name(), 'Router and URL generator resolve named framework routes.');
    }
}
