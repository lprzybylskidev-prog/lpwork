<?php

declare(strict_types=1);

namespace LPWork\Events;

use Closure;
use LPWork\Events\Exceptions\DuplicateListenerException;

/**
 * Stores and resolves event registry registrations.
 */
final class EventRegistry
{
    /**
     * @var array<class-string, list<class-string|Closure(object): void>>
     */
    private array $listeners = [];

    /**
     * @param class-string $event
     * @param list<class-string|Closure(object): void> $listeners
     */
    public function add(string $event, array $listeners): void
    {
        foreach ($listeners as $listener) {
            if ($this->alreadyRegistered($event, $listener)) {
                throw new DuplicateListenerException($event, $this->name($listener));
            }

            $this->listeners[$event][] = $listener;
        }
    }

    /**
     * @return list<class-string|Closure(object): void>
     */
    public function listenersFor(object $event): array
    {
        return $this->listeners[$event::class] ?? [];
    }

    /**
     * @param class-string|Closure(object): void $listener
     */
    private function alreadyRegistered(string $event, string|Closure $listener): bool
    {
        foreach ($this->listeners[$event] ?? [] as $registered) {
            if ($this->name($registered) === $this->name($listener)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param class-string|Closure(object): void $listener
     */
    private function name(string|Closure $listener): string
    {
        return is_string($listener) ? $listener : 'closure:' . spl_object_id($listener);
    }
}
