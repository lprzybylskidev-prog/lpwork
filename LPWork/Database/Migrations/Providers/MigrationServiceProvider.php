<?php

declare(strict_types=1);

namespace LPWork\Database\Migrations\Providers;

use LPWork\Console\ConsoleTableRenderer;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Database\DatabaseManager;
use LPWork\Database\Migrations\Commands\MigrateCommand;
use LPWork\Database\Migrations\Commands\MigrateFreshCommand;
use LPWork\Database\Migrations\Commands\MigrateRollbackCommand;
use LPWork\Database\Migrations\Commands\MigrateStatusCommand;
use LPWork\Database\Migrations\MigrationRegistry;
use LPWork\Database\Migrations\MigrationResolver;
use LPWork\Database\Migrations\MigrationStatusRenderer;
use LPWork\Database\Migrations\Migrator;
use LPWork\Database\Seeders\DatabaseSeeder;
use LPWork\Foundation\ServiceProvider;

/**
 * Registers migration service provider services with the framework container.
 */
final class MigrationServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(MigrationRegistry::class);
        $container->singleton(MigrationResolver::class, static fn(Container $container): MigrationResolver => new MigrationResolver($container));
        $container->singleton(Migrator::class, static function (Container $container): Migrator {
            $database = $container->make(DatabaseManager::class);
            $registry = $container->make(MigrationRegistry::class);
            $resolver = $container->make(MigrationResolver::class);

            if (!$database instanceof DatabaseManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DatabaseManager::class);
            }

            if (!$registry instanceof MigrationRegistry) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(MigrationRegistry::class);
            }

            if (!$resolver instanceof MigrationResolver) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(MigrationResolver::class);
            }

            return new Migrator($database, $registry, $resolver);
        });
        $container->singleton(MigrationStatusRenderer::class, static function (Container $container): MigrationStatusRenderer {
            $renderer = $container->make(ConsoleTableRenderer::class);

            if (!$renderer instanceof ConsoleTableRenderer) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(ConsoleTableRenderer::class);
            }

            return new MigrationStatusRenderer($renderer);
        });
        $container->singleton(MigrateCommand::class, static function (Container $container): MigrateCommand {
            $migrator = $container->make(Migrator::class);
            $seeder = $container->make(DatabaseSeeder::class);

            if (!$migrator instanceof Migrator) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Migrator::class);
            }

            if (!$seeder instanceof DatabaseSeeder) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DatabaseSeeder::class);
            }

            return new MigrateCommand(
                migrator: $migrator,
                seeder: $seeder,
            );
        });
        $container->singleton(MigrateFreshCommand::class);
        $container->singleton(MigrateRollbackCommand::class);
        $container->singleton(MigrateStatusCommand::class);

        $this->registerCommands($container, [
            MigrateCommand::class,
            MigrateFreshCommand::class,
            MigrateRollbackCommand::class,
            MigrateStatusCommand::class,
        ]);
    }
}
