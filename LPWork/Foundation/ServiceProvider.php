<?php

declare(strict_types=1);

namespace LPWork\Foundation;

use Closure;
use LPWork\Console\CommandRegistry;
use LPWork\Console\Contracts\Command;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Database\Migrations\Contracts\Migration;
use LPWork\Database\Migrations\MigrationRegistry;
use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Contracts\CompiledCache;
use LPWork\Foundation\Contracts\ServiceProvider as ServiceProviderContract;
use LPWork\Foundation\Exceptions\ProviderFileNotFoundException;
use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckRegistrar;

/**
 * Registers service provider services with the framework container.
 */
abstract class ServiceProvider implements ServiceProviderContract
{
    protected function load(string $path, Container $container): void
    {
        if (!new Filesystem()->isFile($path)) {
            throw new ProviderFileNotFoundException($path);
        }

        require $path;
    }

    /**
     * @param class-string $id
     */
    protected static function optional(Container $container, string $id): ?object
    {
        try {
            return $container->make($id);
        } catch (CannotResolveDependencyException) {
            return null;
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    protected function require(Container $container, string $id): object
    {
        $resolved = $container->make($id);

        if (!$resolved instanceof $id) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject($id);
        }

        return $resolved;
    }

    /**
     * @param list<class-string<Command>> $commands
     */
    protected function registerCommands(Container $container, array $commands): void
    {
        $registry = self::optional($container, CommandRegistry::class);

        if (!$registry instanceof CommandRegistry) {
            return;
        }

        foreach ($commands as $command) {
            try {
                $resolved = $container->make($command);
            } catch (CannotResolveDependencyException) {
                continue;
            }

            if (!$resolved instanceof Command) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject($command);
            }

            $registry->add($resolved);
        }
    }

    /**
     * @param list<class-string<CompiledCache>> $caches
     */
    protected function registerCompiledCaches(Container $container, array $caches): void
    {
        $registry = self::optional($container, CompiledCacheRegistry::class);

        if (!$registry instanceof CompiledCacheRegistry) {
            return;
        }

        foreach ($caches as $cache) {
            try {
                $resolved = $container->make($cache);
            } catch (CannotResolveDependencyException) {
                continue;
            }

            if (!$resolved instanceof CompiledCache) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject($cache);
            }

            $registry->add($resolved);
        }
    }

    /**
     * @param Closure(Container): HttpDebugContextProvider $provider
     */
    protected function registerHttpDebugContextProvider(Container $container, Closure $provider): void
    {
        $context = self::optional($container, HttpDebugContext::class);

        if (!$context instanceof HttpDebugContext) {
            return;
        }

        $context->addProvider($provider($container));
    }

    /**
     * @param class-string<HealthCheck> $check
     */
    protected function registerHealthCheck(Container $container, string $check): void
    {
        new HealthCheckRegistrar()->register($container, $check);
    }

    /**
     * @param list<class-string<Migration>> $migrations
     */
    protected function registerFrameworkMigrations(Container $container, string $connection, array $migrations): void
    {
        $registry = self::optional($container, MigrationRegistry::class);

        if (!$registry instanceof MigrationRegistry) {
            return;
        }

        $registry->add($connection, $migrations);
    }
}
