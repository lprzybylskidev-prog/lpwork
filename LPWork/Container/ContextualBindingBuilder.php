<?php

declare(strict_types=1);

namespace LPWork\Container;

/**
 * Represents the contextual binding builder framework component.
 */
final readonly class ContextualBindingBuilder
{
    /**
     * Creates a new ContextualBindingBuilder instance.
     */
    public function __construct(
        private Container $container,
        private string $concrete,
    ) {}

    /**
     * Performs the needs operation.
     */
    public function needs(string $abstract): ContextualBindingNeedsBuilder
    {
        return new ContextualBindingNeedsBuilder(
            container: $this->container,
            concrete: $this->concrete,
            abstract: $abstract,
        );
    }
}
