<?php

declare(strict_types=1);

namespace Tests\support\events;

use LPWork\Container\Container;
use LPWork\Events\EventDispatcher;
use LPWork\Events\EventRegistry;
use LPWork\Events\Providers\EventServiceProvider;
use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\LogChannel;
use RuntimeException;
use Tests\support\testing\Logging\TestLogDriver;

final readonly class EventDispatcherFactory
{
    public static function withLogger(TestLogDriver $driver): EventDispatcher
    {
        $container = new Container();
        $container->instance(Logger::class, new LogChannel('app', $driver));
        new EventServiceProvider()->register($container);

        return self::dispatcher($container);
    }

    /**
     * @param class-string $event
     */
    public static function withListener(object $listener, string $event): EventDispatcher
    {
        $container = new Container();
        new EventServiceProvider()->register($container);
        $container->instance($listener::class, $listener);

        $registry = $container->make(EventRegistry::class);

        if (!$registry instanceof EventRegistry) {
            throw new RuntimeException('Could not resolve event registry.');
        }

        $registry->add($event, [$listener::class]);

        return self::dispatcher($container);
    }

    private static function dispatcher(Container $container): EventDispatcher
    {
        $dispatcher = $container->make(EventDispatcher::class);

        if (!$dispatcher instanceof EventDispatcher) {
            throw new RuntimeException('Could not resolve event dispatcher.');
        }

        return $dispatcher;
    }
}
