<?php

declare(strict_types=1);

namespace LPWork\Throttle;

/**
 * Represents the throttle policy framework component.
 */
final readonly class ThrottlePolicy
{
    /**
     * Creates a new ThrottlePolicy instance.
     */
    public function __construct(
        private string $name,
        private bool $enabled,
        private int $maxAttempts,
        private int $decaySeconds,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Performs the enabled operation.
     */
    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Performs the max attempts operation.
     */
    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Performs the decay seconds operation.
     */
    public function decaySeconds(): int
    {
        return $this->decaySeconds;
    }
}
