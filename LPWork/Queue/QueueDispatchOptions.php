<?php

declare(strict_types=1);

namespace LPWork\Queue;

/**
 * Carries options for queue dispatch options behavior.
 */
final readonly class QueueDispatchOptions
{
    /**
     * Creates a new QueueDispatchOptions instance.
     */
    public function __construct(
        public ?string $connection = null,
        public ?string $queue = null,
        public int $delaySeconds = 0,
        public ?int $maxAttempts = null,
    ) {}
}
