<?php
declare(strict_types=1);

namespace Config;

use LPwork\Event\Contract\EventProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Application-level event provider (PSR-14).
 */
class EventProvider implements EventProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getListeners(): array
    {
        /** @var array<string, array<int, mixed>> $listeners */
        $listeners = [];

        return $listeners;
    }

    /**
     * @inheritDoc
     */
    public function getSubscribers(): array
    {
        /** @var array<int, class-string<EventSubscriberInterface>> $subscribers */
        $subscribers = [];

        return $subscribers;
    }
}
