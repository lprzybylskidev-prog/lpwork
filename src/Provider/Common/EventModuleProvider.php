<?php
declare(strict_types=1);

namespace LPwork\Provider\Common;

use DI\ContainerBuilder;
use LPwork\Event\Contract\EventBusInterface;
use LPwork\Event\Contract\EventProviderInterface;
use LPwork\Event\EventBus;
use LPwork\Event\EventDispatcherFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Registers event dispatcher and bus.
 */
final class EventModuleProvider
{
    /**
     * @param ContainerBuilder $containerBuilder
     */
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            EventDispatcherFactory::class => \DI\autowire(EventDispatcherFactory::class),
            EventProviderInterface::class => \DI\get(\Config\EventProvider::class),
            EventDispatcher::class => \DI\factory(static function (
                EventDispatcherFactory $factory,
                EventProviderInterface $provider,
            ): EventDispatcher {
                return $factory->create($provider);
            }),
            EventDispatcherInterface::class => \DI\get(EventDispatcher::class),
            ListenerProviderInterface::class => \DI\get(EventDispatcher::class),
            EventBusInterface::class => \DI\autowire(EventBus::class),
        ]);
    }
}
