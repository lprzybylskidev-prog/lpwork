<?php

declare(strict_types=1);

namespace LPWork\Session\Providers;

use LPWork\Cache\CacheManager;
use LPWork\Config\ArrayConfigReader;
use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Database\DatabaseManager;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\SessionHealthCheck;
use LPWork\Middleware\SessionMiddleware;
use LPWork\Security\SecurityConfig;
use LPWork\Session\Contracts\SessionDriver;
use LPWork\Session\Exceptions\InvalidSessionConfigException;
use LPWork\Session\Exceptions\MissingSessionConfigException;
use LPWork\Session\Migrations\CreateSessionsTable;
use LPWork\Session\SessionDriverFactory;
use LPWork\Session\SessionManager;
use LPWork\Time\Contracts\Clock;

/**
 * Registers session service provider services with the framework container.
 */
final class SessionServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(SessionManager::class, static function (Container $container): SessionManager {
            $security = $container->make(SecurityConfig::class);
            $cache = $container->make(CacheManager::class);
            $database = $container->make(DatabaseManager::class);
            $clock = $container->make(Clock::class);

            if (!$security instanceof SecurityConfig) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(SecurityConfig::class);
            }

            if (!$cache instanceof CacheManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(CacheManager::class);
            }

            if (!$database instanceof DatabaseManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(DatabaseManager::class);
            }

            if (!$clock instanceof Clock) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(Clock::class);
            }

            return new SessionManager(Config::getArray('session'), new SessionDriverFactory($security, $cache, $database, $clock));
        });

        $container->singleton(SessionDriver::class, static function (Container $container): SessionDriver {
            $manager = $container->make(SessionManager::class);

            if (!$manager instanceof SessionManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(SessionManager::class);
            }

            return $manager->default();
        });

        $container->bind(SessionMiddleware::class, static function (Container $container): SessionMiddleware {
            $driver = $container->make(SessionDriver::class);

            if (!$driver instanceof SessionDriver) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(SessionDriver::class);
            }

            return new SessionMiddleware($driver);
        });

        $container->singleton(SessionHealthCheck::class);
        $this->registerHealthCheck($container, SessionHealthCheck::class);
        $this->registerSessionMigrations($container);
    }

    private function registerSessionMigrations(Container $container): void
    {
        $reader = new ArrayConfigReader(
            config: Config::getArray('session'),
            missingException: static fn(string $key): MissingSessionConfigException => new MissingSessionConfigException($key),
            invalidException: static fn(string $key): InvalidSessionConfigException => new InvalidSessionConfigException($key),
        );

        foreach ($reader->arrayMap('drivers') as $name => $driver) {
            $driverReader = new ArrayConfigReader(
                config: $driver,
                missingException: static fn(string $key): MissingSessionConfigException => new MissingSessionConfigException($key),
                invalidException: static fn(string $key): InvalidSessionConfigException => new InvalidSessionConfigException($key),
            );

            if ($driverReader->string('driver', "drivers.{$name}.driver") !== 'database') {
                continue;
            }

            $table = $driverReader->string('table', "drivers.{$name}.table");
            $connection = $driver['connection'] ?? null;
            $container->singleton(CreateSessionsTable::class, static fn(): CreateSessionsTable => new CreateSessionsTable($table));
            parent::registerFrameworkMigrations(
                $container,
                is_string($connection) && $connection !== '' ? $connection : 'default',
                [CreateSessionsTable::class],
            );
        }
    }
}
