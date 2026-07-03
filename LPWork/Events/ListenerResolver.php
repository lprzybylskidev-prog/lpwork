<?php

declare(strict_types=1);

namespace LPWork\Events;

use Closure;
use LPWork\Container\Container;
use LPWork\Events\Exceptions\InvalidListenerException;

/**
 * Resolves listener resolver values into runtime objects.
 */
final readonly class ListenerResolver
{
    /**
     * Creates a new ListenerResolver instance.
     */
    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param class-string|Closure(object): void $listener
     *
     * @return callable(object): void
     */
    public function resolve(string|Closure $listener): callable
    {
        if ($listener instanceof Closure) {
            return $listener;
        }

        $instance = $this->container->make($listener);

        if (!method_exists($instance, 'handle')) {
            throw InvalidListenerException::missingHandle($listener);
        }

        $callable = [$instance, 'handle'];

        return $callable;
    }

    /**
     * @param class-string|Closure(object): void $listener
     */
    public function name(string|Closure $listener): string
    {
        return is_string($listener) ? $listener : 'closure:' . spl_object_id($listener);
    }
}
