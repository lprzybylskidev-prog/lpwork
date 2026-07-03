<?php

declare(strict_types=1);

namespace LPWork\Container;

use Closure;

/**
 * Represents the contextual binding needs builder framework component.
 */
final readonly class ContextualBindingNeedsBuilder
{
    /**
     * Creates a new ContextualBindingNeedsBuilder instance.
     */
    public function __construct(
        private Container $container,
        private string $concrete,
        private string $abstract,
    ) {}

    /**
     * @param string|Closure(Container): mixed $implementation
     */
    public function give(string|Closure $implementation): void
    {
        $this->container->addContextualBinding(
            concrete: $this->concrete,
            abstract: $this->abstract,
            implementation: $implementation,
        );
    }
}
