<?php

declare(strict_types=1);

namespace LPWork\Events\Providers;

use Closure;
use LPWork\Container\Container;
use LPWork\Events\EventRegistry;

/**
 * Registers events provider services with the framework container.
 */
abstract class EventsProvider extends \LPWork\Foundation\ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $registry = $container->make(EventRegistry::class);

        if (!$registry instanceof EventRegistry) {
            return;
        }

        foreach ($this->listeners() as $event => $listeners) {
            $registry->add($event, $listeners);
        }
    }

    /**
     * @return array<class-string, list<class-string|Closure(object): void>>
     */
    abstract protected function listeners(): array;
}
