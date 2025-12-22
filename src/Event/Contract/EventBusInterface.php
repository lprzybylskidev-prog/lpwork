<?php
declare(strict_types=1);

namespace LPwork\Event\Contract;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Application-facing event bus.
 */
interface EventBusInterface extends EventDispatcherInterface
{
    /**
     * Dispatches an event synchronously.
     *
     * @param object $event
     *
     * @return object
     */
    public function dispatch(object $event): object;
}
