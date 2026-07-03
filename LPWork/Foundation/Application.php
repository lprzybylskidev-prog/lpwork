<?php

declare(strict_types=1);

namespace LPWork\Foundation;

use LPWork\Container\Container;
use LPWork\Foundation\Contracts\ServiceProvider;

/**
 * Represents the application framework component.
 */
final class Application
{
    private readonly Container $container;

    /**
     * Creates a new Application instance.
     */
    public function __construct(
        private readonly string $basePath,
        ?Container $container = null,
    ) {
        $this->container = $container ?? new Container();
    }

    /**
     * Performs the base path operation.
     */
    public function basePath(string $path = ''): string
    {
        if ($path === '') {
            return $this->basePath;
        }

        return $this->basePath . '/' . ltrim($path, '/');
    }

    /**
     * Performs the container operation.
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(ServiceProvider $provider): void
    {
        $provider->register($this->container);
    }
}
