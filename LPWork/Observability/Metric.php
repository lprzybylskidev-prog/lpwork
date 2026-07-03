<?php

declare(strict_types=1);

namespace LPWork\Observability;

/**
 * Represents the metric framework component.
 */
final readonly class Metric
{
    /**
     * @param array<string, string|int|float|bool|null> $tags
     */
    public function __construct(
        public string $name,
        public int|float $value,
        public string $unit = 'count',
        public array $tags = [],
        public float $recordedAtMs = 0.0,
        public int $memoryBytes = 0,
    ) {}
}
