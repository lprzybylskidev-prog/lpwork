<?php

declare(strict_types=1);

namespace LPWork\Security;

use LPWork\ErrorHandling\Contracts\HttpDebugContextProvider;
use LPWork\ErrorHandling\HttpDebugContext;

/**
 * Registers security debug context provider services with the framework container.
 */
final readonly class SecurityDebugContextProvider implements HttpDebugContextProvider
{
    /**
     * Creates a new SecurityDebugContextProvider instance.
     */
    public function __construct(
        private SecurityDebugCollector $collector,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function context(HttpDebugContext $context): array
    {
        return [
            'Security' => [
                'Denials' => array_map(static fn(SecurityDebugRecord $record): array => [
                    'Reason' => $record->reason,
                    'Message' => $record->message,
                    'Context' => $record->context,
                ], $this->collector->recent()),
            ],
        ];
    }
}
