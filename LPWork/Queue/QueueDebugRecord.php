<?php

declare(strict_types=1);

namespace LPWork\Queue;

/**
 * Represents the queue debug record framework component.
 */
final readonly class QueueDebugRecord
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $status,
        public string $connection,
        public string $queue,
        public string $job,
        public string $id,
        public ?float $durationMs = null,
        public array $context = [],
    ) {}
}
