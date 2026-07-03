<?php

declare(strict_types=1);

namespace LPWork\Database\Seeders\Providers;

use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Database\DatabaseManager;
use LPWork\Database\Seeders\Commands\DatabaseSeedCommand;
use LPWork\Database\Seeders\DatabaseSeeder;
use LPWork\Database\Seeders\SeederRegistry;
use LPWork\Database\Seeders\SeederResolver;
use LPWork\Foundation\ServiceProvider;

/**
 * Registers seeder service provider services with the framework container.
 */
final class SeederServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(SeederRegistry::class);
        $container->singleton(SeederResolver::class, static fn(Container $container): SeederResolver => new SeederResolver($container));
        $container->singleton(DatabaseSeeder::class, static function (Container $container): DatabaseSeeder {
            $database = $container->make(DatabaseManager::class);
            $registry = $container->make(SeederRegistry::class);
            $resolver = $container->make(SeederResolver::class);

            if (!$database instanceof DatabaseManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DatabaseManager::class);
            }

            if (!$registry instanceof SeederRegistry) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(SeederRegistry::class);
            }

            if (!$resolver instanceof SeederResolver) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(SeederResolver::class);
            }

            return new DatabaseSeeder($database, $registry, $resolver);
        });
        $container->singleton(DatabaseSeedCommand::class);

        $this->registerCommands($container, [DatabaseSeedCommand::class]);
    }
}
