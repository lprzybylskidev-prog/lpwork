<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Providers;

use LPWork\Broadcasting\BroadcastChannelRegistry;
use LPWork\Broadcasting\BroadcastDriverFactory;
use LPWork\Broadcasting\BroadcastManager;
use LPWork\Broadcasting\Contracts\Broadcaster;
use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Events\EventDispatcher;
use LPWork\Foundation\ServiceProvider;
use LPWork\Health\Checks\BroadcastingHealthCheck;
use LPWork\Logging\LogManager;

/**
 * Registers broadcasting service provider services with the framework container.
 */
final class BroadcastingServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(BroadcastChannelRegistry::class);
        $container->singleton(BroadcastManager::class, static function (Container $container): BroadcastManager {
            $logger = null;

            if ($container->has(LogManager::class)) {
                $logs = $container->make(LogManager::class);

                if ($logs instanceof LogManager) {
                    $logger = $logs->channel(Config::getString('broadcasting.logging.channel'));
                }
            }

            $events = self::optional($container, EventDispatcher::class);

            return new BroadcastManager(
                config: Config::getArray('broadcasting'),
                driverFactory: new BroadcastDriverFactory($logger),
                events: $events instanceof EventDispatcher ? $events : null,
            );
        });
        $container->singleton(Broadcaster::class, static function (Container $container): Broadcaster {
            $manager = $container->make(BroadcastManager::class);

            if (!$manager instanceof BroadcastManager) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(BroadcastManager::class);
            }

            return $manager->default();
        });

        $container->singleton(BroadcastingHealthCheck::class);
        $this->registerHealthCheck($container, BroadcastingHealthCheck::class);
    }

}
