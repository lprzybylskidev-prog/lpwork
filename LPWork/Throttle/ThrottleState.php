<?php

declare(strict_types=1);

namespace LPWork\Throttle;

/**
 * Represents the throttle state framework component.
 */
final readonly class ThrottleState
{
    /**
     * Creates a new ThrottleState instance.
     */
    public function __construct(
        private int $attempts,
        private int $retryAfter,
    ) {}

    /**
     * Performs the attempts operation.
     */
    public function attempts(): int
    {
        return $this->attempts;
    }

    /**
     * Performs the retry after operation.
     */
    public function retryAfter(): int
    {
        return $this->retryAfter;
    }
}
