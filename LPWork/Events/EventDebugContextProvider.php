<?php

declare(strict_types=1);

namespace LPWork\Events;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;

/**
 * Registers event debug context provider services with the framework container.
 */
final readonly class EventDebugContextProvider implements HttpDebugContextProvider
{
    /**
     * Creates a new EventDebugContextProvider instance.
     */
    public function __construct(
        private EventDebugCollector $collector,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array
    {
        return [
            'Events' => array_map(static fn(EventDispatchRecord $record): array => [
                'event' => $record->event,
                'listeners' => $record->listeners,
                'Duration ms' => $record->durationMs,
                'Successful' => $record->successful,
            ], $this->collector->recent()),
        ];
    }
}
