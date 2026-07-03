<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Providers;

use LPWork\Broadcasting\BroadcastChannelRegistry;
use LPWork\Container\Container;
use LPWork\Foundation\ServiceProvider;

/**
 * Registers broadcast channels provider services with the framework container.
 */
abstract class BroadcastChannelsProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $registry = $container->make(BroadcastChannelRegistry::class);

        if (!$registry instanceof BroadcastChannelRegistry) {
            return;
        }

        $this->channels($registry);
    }

    abstract protected function channels(BroadcastChannelRegistry $channels): void;
}
