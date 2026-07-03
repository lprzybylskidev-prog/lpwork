<?php

declare(strict_types=1);

namespace LPWork\Storage\Providers;

use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\StorageHealthCheck;
use LPWork\Storage\StorageDisk;
use LPWork\Storage\StorageDriverFactory;
use LPWork\Storage\StorageManager;

/**
 * Registers storage service provider services with the framework container.
 */
final class StorageServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(Filesystem::class);

        $container->singleton(StorageManager::class, static function (Container $container): StorageManager {
            $app = $container->make(Application::class);
            $filesystem = $container->make(Filesystem::class);

            if (!$app instanceof Application) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
            }

            if (!$filesystem instanceof Filesystem) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Filesystem::class);
            }

            return new StorageManager(
                config: Config::getArray('storage'),
                basePath: $app->basePath(),
                driverFactory: new StorageDriverFactory($app->basePath(), $filesystem),
            );
        });

        $container->singleton(StorageDisk::class, static function (Container $container): StorageDisk {
            $manager = $container->make(StorageManager::class);

            if (!$manager instanceof StorageManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(StorageManager::class);
            }

            return $manager->default();
        });
        $container->singleton(StorageHealthCheck::class, static function (Container $container): StorageHealthCheck {
            $manager = $container->make(StorageManager::class);

            if (!$manager instanceof StorageManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(StorageManager::class);
            }

            return new StorageHealthCheck($manager);
        });

        $this->registerHealthCheck($container, StorageHealthCheck::class);
    }
}
