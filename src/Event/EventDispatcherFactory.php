<?php
declare(strict_types=1);

namespace LPwork\Event;

use LPwork\Event\Contract\EventProviderInterface;
use LPwork\Event\Exception\EventConfigurationException;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Builds the framework event dispatcher from configuration and container.
 */
final class EventDispatcherFactory
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Creates the dispatcher and wires configured listeners/subscribers.
     *
     * @param EventProviderInterface $provider
     *
     * @return EventDispatcher
     */
    public function create(EventProviderInterface $provider): EventDispatcher
    {
        $dispatcher = new EventDispatcher();

        foreach ($provider->getListeners() as $event => $listeners) {
            if (!\is_string($event)) {
                continue;
            }

            foreach ((array) $listeners as $listener) {
                $dispatcher->addListener($event, $this->normalizeListener($listener));
            }
        }

        foreach ($provider->getSubscribers() as $subscriberClass) {
            if (!\is_string($subscriberClass)) {
                continue;
            }

            $subscriber = $this->container->get($subscriberClass);

            if (!$subscriber instanceof EventSubscriberInterface) {
                throw new EventConfigurationException(
                    \sprintf(
                        'Subscriber "%s" must implement %s.',
                        $subscriberClass,
                        EventSubscriberInterface::class,
                    ),
                );
            }

            $dispatcher->addSubscriber($subscriber);
        }

        return $dispatcher;
    }

    /**
     * @param mixed $listener
     *
     * @return callable
     */
    private function normalizeListener(mixed $listener): callable
    {
        if (\is_callable($listener)) {
            /** @var callable $listener */
            return $listener;
        }

        if (\is_string($listener)) {
            $resolved = $this->container->get($listener);

            if (!\is_callable($resolved)) {
                throw new EventConfigurationException(
                    \sprintf('Listener "%s" is not callable after resolution.', $listener),
                );
            }

            /** @var callable $resolved */
            return $resolved;
        }

        if (\is_array($listener) && \count($listener) === 2) {
            [$target, $method] = $listener;

            if (\is_string($target)) {
                $instance = $this->container->get($target);
                $callable = [$instance, $method];
            } else {
                $callable = [$target, $method];
            }

            if (!\is_callable($callable)) {
                throw new EventConfigurationException(
                    \sprintf(
                        'Array listener is not callable: [%s, %s]',
                        (string) $target,
                        (string) $method,
                    ),
                );
            }

            /** @var callable $callable */
            return $callable;
        }

        throw new EventConfigurationException('Listener definition is not callable.');
    }
}
