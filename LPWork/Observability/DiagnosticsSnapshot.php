<?php

declare(strict_types=1);

namespace LPWork\Observability;

/**
 * Represents the diagnostics snapshot framework component.
 */
final readonly class DiagnosticsSnapshot
{
    /**
     * @param array<string, mixed> $groups
     * @param list<Metric> $metrics
     * @param list<array{channel: string, level: string, message: string, context: array<string, mixed>}> $logs
     */
    public function __construct(
        public array $groups,
        public array $metrics,
        public array $logs,
    ) {}
}
