<?php
declare(strict_types=1);

namespace LPwork\Event;

use LPwork\Event\Contract\EventBusInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Default synchronous event bus delegating to the PSR-14 dispatcher.
 */
final class EventBus implements EventBusInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(object $event): object
    {
        return $this->dispatcher->dispatch($event);
    }
}
