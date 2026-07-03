<?php

declare(strict_types=1);

namespace App\Shared\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Environment\Environment;

/**
 * Configures in-memory metrics retention and optional metrics reporters.
 */
final class ObservabilityConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'observability';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        $reporters = array_filter(
            explode(',', Environment::get('METRICS_REPORTERS', 'null')),
            static fn(string $reporter): bool => $reporter !== '',
        );

        return [
            'metrics' => [
                // METRICS_MEMORY_LIMIT caps retained in-process measurements for diagnostics.
                'limit' => (int) Environment::get('METRICS_MEMORY_LIMIT', '100'),
                'reporters' => $this->reporters($reporters),
                'enabled_reporters' => $reporters,
            ],
        ];
    }

    /**
     * @param array<array-key, string> $reporters
     * @return array<string, array<array-key, mixed>>
     */
    private function reporters(array $reporters): array
    {
        $configured = [];

        foreach ($reporters as $reporter) {
            $configured[$reporter] = match ($reporter) {
                // Log reporter writes metrics to the configured logging stack.
                'log' => [
                    'driver' => 'log',
                ],
                // Prometheus reporter writes a text exposition file.
                'prometheus' => [
                    'driver' => 'prometheus',
                    'path' => Environment::get('METRICS_PROMETHEUS_PATH', 'storage/metrics/prometheus.prom'),
                ],
                // StatsD reporter sends metrics to a UDP StatsD-compatible collector.
                'statsd' => [
                    'driver' => 'statsd',
                    'host' => Environment::get('METRICS_STATSD_HOST', '127.0.0.1'),
                    'port' => (int) Environment::get('METRICS_STATSD_PORT', '8125'),
                    'prefix' => Environment::get('METRICS_STATSD_PREFIX', 'lpwork'),
                ],
                // Null reporter disables external metric reporting.
                'null' => [
                    'driver' => 'null',
                ],
                default => [
                    'driver' => $reporter,
                ],
            };
        }

        return $configured;
    }
}
