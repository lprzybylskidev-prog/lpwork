<?php

declare(strict_types=1);

namespace LPWork\Security;

/**
 * Represents the security debug record framework component.
 */
final readonly class SecurityDebugRecord
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $reason,
        public string $message,
        public array $context,
    ) {}
}
