<?php

declare(strict_types=1);

namespace LPWork\Health;

use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Health\Contracts\HealthCheck;

/**
 * Represents the health check registrar framework component.
 */
final readonly class HealthCheckRegistrar
{
    /**
     * @param class-string<HealthCheck> $check
     */
    public function register(Container $container, string $check): void
    {
        try {
            $registry = $container->make(HealthCheckRegistry::class);
            $resolved = $container->make($check);
        } catch (CannotResolveDependencyException) {
            return;
        }

        if (!$registry instanceof HealthCheckRegistry) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject(HealthCheckRegistry::class);
        }

        if (!$resolved instanceof HealthCheck) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject($check);
        }

        $registry->add($resolved);
    }
}
