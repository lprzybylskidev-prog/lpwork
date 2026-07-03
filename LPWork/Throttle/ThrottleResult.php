<?php

declare(strict_types=1);

namespace LPWork\Throttle;

/**
 * Represents the result of throttle result work.
 */
final readonly class ThrottleResult
{
    /**
     * Creates a new ThrottleResult instance.
     */
    public function __construct(
        private bool $allowed,
        private int $attempts,
        private int $maxAttempts,
        private int $retryAfter,
    ) {}

    /**
     * Returns allowed without limit.
     */
    public static function allowedWithoutLimit(): self
    {
        return new self(
            allowed: true,
            attempts: 0,
            maxAttempts: 0,
            retryAfter: 0,
        );
    }

    /**
     * Returns allowed.
     */
    public function allowed(): bool
    {
        return $this->allowed;
    }

    /**
     * Performs the attempts operation.
     */
    public function attempts(): int
    {
        return $this->attempts;
    }

    /**
     * Performs the max attempts operation.
     */
    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Performs the retry after operation.
     */
    public function retryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Performs the remaining operation.
     */
    public function remaining(): int
    {
        return max(0, $this->maxAttempts - $this->attempts);
    }
}
