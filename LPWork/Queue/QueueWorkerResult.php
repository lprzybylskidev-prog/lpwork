<?php

declare(strict_types=1);

namespace LPWork\Queue;

/**
 * Represents the result of queue worker result work.
 */
final readonly class QueueWorkerResult
{
    /**
     * Creates a new QueueWorkerResult instance.
     */
    public function __construct(
        public int $processed,
        public int $failed,
    ) {}
}
