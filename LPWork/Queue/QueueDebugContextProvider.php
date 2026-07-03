<?php

declare(strict_types=1);

namespace LPWork\Queue;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;

/**
 * Registers queue debug context provider services with the framework container.
 */
final readonly class QueueDebugContextProvider implements HttpDebugContextProvider
{
    /**
     * Creates a new QueueDebugContextProvider instance.
     */
    public function __construct(
        private QueueDebugCollector $collector,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array
    {
        return [
            'Queue' => [
                'Jobs' => array_map(static fn(QueueDebugRecord $record): array => [
                    'Status' => $record->status,
                    'Connection' => $record->connection,
                    'Queue' => $record->queue,
                    'Job' => $record->job,
                    'ID' => $record->id,
                    'Duration ms' => $record->durationMs,
                    'Context' => $record->context,
                ], $this->collector->recent()),
            ],
        ];
    }
}
