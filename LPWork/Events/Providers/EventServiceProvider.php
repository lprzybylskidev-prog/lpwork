<?php

declare(strict_types=1);

namespace LPWork\Events\Providers;

use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Events\EventDebugCollector;
use LPWork\Events\EventDebugContextProvider;
use LPWork\Events\EventDispatcher;
use LPWork\Events\EventRegistry;
use LPWork\Events\ListenerResolver;
use LPWork\Foundation\ServiceProvider;
use LPWork\Kernels\Cli\Events\CliCommandFailed;
use LPWork\Kernels\Cli\Events\CliCommandHandled;
use LPWork\Kernels\Http\Events\HttpRequestFailed;
use LPWork\Kernels\Http\Events\HttpRequestHandled;
use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\Listeners\LogCliCommandFailed;
use LPWork\Logging\Listeners\LogCliCommandHandled;
use LPWork\Logging\Listeners\LogCliCommandThrottled;
use LPWork\Logging\Listeners\LogHttpRequestFailed;
use LPWork\Logging\Listeners\LogHttpRequestHandled;
use LPWork\Logging\Listeners\LogHttpRequestThrottled;
use LPWork\Logging\Listeners\LogHttpSecurityDenied;
use LPWork\Observability\MetricCollector;
use LPWork\Security\Events\HttpSecurityDenied;
use LPWork\Throttle\Events\CliCommandThrottled;
use LPWork\Throttle\Events\HttpRequestThrottled;

/**
 * Registers event service provider services with the framework container.
 */
final class EventServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(EventRegistry::class);
        $container->singleton(EventDebugCollector::class, static function (Container $container): EventDebugCollector {
            $metrics = $container->has(MetricCollector::class) ? $container->make(MetricCollector::class) : null;

            if ($metrics !== null && !$metrics instanceof MetricCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(MetricCollector::class);
            }

            return new EventDebugCollector(metrics: $metrics);
        });
        $container->singleton(ListenerResolver::class, static fn(Container $container): ListenerResolver => new ListenerResolver($container));
        $container->singleton(EventDispatcher::class);
        $container->singleton(EventDebugContextProvider::class, static function (Container $container): EventDebugContextProvider {
            $collector = $container->make(EventDebugCollector::class);

            if (!$collector instanceof EventDebugCollector) {
                throw CannotResolveDependencyException::factoryDidNotReturnObject(EventDebugCollector::class);
            }

            return new EventDebugContextProvider($collector);
        });

        $this->registerFrameworkListeners($container);
        $this->registerDebugContext($container);
    }

    private function registerFrameworkListeners(Container $container): void
    {
        if (!$container->has(Logger::class)) {
            return;
        }

        $registry = $container->make(EventRegistry::class);

        if (!$registry instanceof EventRegistry) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject(EventRegistry::class);
        }

        $registry->add(HttpRequestHandled::class, [LogHttpRequestHandled::class]);
        $registry->add(HttpRequestFailed::class, [LogHttpRequestFailed::class]);
        $registry->add(CliCommandHandled::class, [LogCliCommandHandled::class]);
        $registry->add(CliCommandFailed::class, [LogCliCommandFailed::class]);
        $registry->add(HttpSecurityDenied::class, [LogHttpSecurityDenied::class]);
        $registry->add(HttpRequestThrottled::class, [LogHttpRequestThrottled::class]);
        $registry->add(CliCommandThrottled::class, [LogCliCommandThrottled::class]);
    }

    private function registerDebugContext(Container $container): void
    {
        $this->registerHttpDebugContextProvider(
            $container,
            static function (Container $container): EventDebugContextProvider {
                $provider = $container->make(EventDebugContextProvider::class);

                if (!$provider instanceof EventDebugContextProvider) {
                    throw CannotResolveDependencyException::factoryDidNotReturnObject(EventDebugContextProvider::class);
                }

                return $provider;
            },
        );
    }
}
