<?php

declare(strict_types=1);

namespace LPWork\Observability;

use LPWork\Observability\Contracts\MetricReporter;

/**
 * Represents the statsd metric reporter framework component.
 */
final readonly class StatsdMetricReporter implements MetricReporter
{
    /**
     * Creates a new StatsdMetricReporter instance.
     */
    public function __construct(
        private string $host = '127.0.0.1',
        private int $port = 8125,
        private string $prefix = 'lpwork',
    ) {}

    /**
     * Performs the report operation.
     */
    public function report(Metric $metric): void
    {
        $name = trim($this->prefix . '.' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $metric->name), '.');
        $type = $metric->unit === 'ms' ? 'ms' : 'g';
        $payload = sprintf('%s:%s|%s', $name, $metric->value, $type);
        $socket = @fsockopen('udp://' . $this->host, $this->port);

        if ($socket === false) {
            return;
        }

        @fwrite($socket, $payload);
        @fclose($socket);
    }
}
