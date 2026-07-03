<?php

declare(strict_types=1);

namespace LPWork\Cache;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;

/**
 * Registers cache debug context provider services with the framework container.
 */
final readonly class CacheDebugContextProvider implements HttpDebugContextProvider
{
    /**
     * Creates a new CacheDebugContextProvider instance.
     */
    public function __construct(
        private CacheDebugCollector $collector,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array
    {
        return [
            'Cache' => [
                'Operations' => array_map(static fn(CacheDebugRecord $record): array => [
                    'Operation' => $record->operation,
                    'Store' => $record->store,
                    'Key' => $record->key,
                    'Duration ms' => $record->durationMs,
                    'Successful' => $record->successful,
                    'Context' => $record->context,
                ], $this->collector->recent()),
            ],
        ];
    }
}
