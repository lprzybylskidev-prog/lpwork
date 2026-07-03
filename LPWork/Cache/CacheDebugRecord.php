<?php

declare(strict_types=1);

namespace LPWork\Cache;

/**
 * Represents the cache debug record framework component.
 */
final readonly class CacheDebugRecord
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $store,
        public string $operation,
        public string $key,
        public float $durationMs,
        public bool $successful,
        public array $context = [],
    ) {}
}
