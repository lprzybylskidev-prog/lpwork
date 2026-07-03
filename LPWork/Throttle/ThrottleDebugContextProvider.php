<?php

declare(strict_types=1);

namespace LPWork\Throttle;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;

/**
 * Registers throttle debug context provider services with the framework container.
 */
final readonly class ThrottleDebugContextProvider implements HttpDebugContextProvider
{
    /**
     * Creates a new ThrottleDebugContextProvider instance.
     */
    public function __construct(
        private ThrottleDebugCollector $collector,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array
    {
        return [
            'Throttle' => [
                'Denials' => array_map(static fn(ThrottleDebugRecord $record): array => [
                    'Flow' => $record->flow,
                    'Context' => $record->context,
                ], $this->collector->recent()),
            ],
        ];
    }
}
