<?php

declare(strict_types=1);

namespace LPWork\Routing;

use LPWork\Foundation\Contracts\ReadableCompiledCache;

/**
 * Represents the route compiled cache framework component.
 */
final readonly class RouteCompiledCache implements ReadableCompiledCache
{
    /**
     * Creates a new RouteCompiledCache instance.
     */
    public function __construct(
        private RouteCache $cache,
        private RouteCollection $routes,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'routes';
    }

    /**
     * Returns label.
     */
    public function label(): string
    {
        return 'Route cache';
    }

    /**
     * Registers or stores aliases.
     */
    public function aliases(): array
    {
        return ['route', 'route:cache'];
    }

    /**
     * Reports whether exists.
     */
    public function exists(): bool
    {
        return $this->cache->exists();
    }

    /**
     * Returns path.
     */
    public function path(): string
    {
        return $this->cache->path();
    }

    /**
     * Performs the rebuild operation.
     */
    public function rebuild(): void
    {
        $this->cache->write($this->routes);
    }
}
