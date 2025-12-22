<?php
declare(strict_types=1);

namespace LPwork\Event\Contract;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides event listeners and subscribers for the dispatcher.
 */
interface EventProviderInterface
{
    /**
     * Returns listeners keyed by event FQCN.
     *
     * @return array<string, array<int, mixed>>
     */
    public function getListeners(): array;

    /**
     * Returns subscriber class names.
     *
     * @return array<int, class-string<EventSubscriberInterface>>
     */
    public function getSubscribers(): array;
}
