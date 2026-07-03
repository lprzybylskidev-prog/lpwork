<?php

declare(strict_types=1);

namespace LPWork\Observability;

use InvalidArgumentException;
use LPWork\Config\ArrayConfigReader;
use LPWork\Foundation\Application;
use LPWork\Logging\Contracts\Logger;
use LPWork\Observability\Contracts\MetricReporter;

/**
 * Creates metric reporter factory instances from framework configuration.
 */
final readonly class MetricReporterFactory
{
    /**
     * Creates a new MetricReporterFactory instance.
     */
    public function __construct(
        private Application $app,
        private ?Logger $logger = null,
    ) {}

    /**
     * @param array<array-key, mixed> $config
     */
    public function create(array $config, string $key): MetricReporter
    {
        $reader = new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): InvalidArgumentException => new InvalidArgumentException("Missing metrics config [{$key}]."),
            invalidException: static fn(string $key): InvalidArgumentException => new InvalidArgumentException("Invalid metrics config [{$key}]."),
        );

        return match ($reader->string('driver', "{$key}.driver")) {
            'null' => new NullMetricReporter(),
            'log' => new LogMetricReporter($this->logger ?? throw new InvalidArgumentException('Metrics log reporter requires a logger.')),
            'prometheus' => new PrometheusMetricReporter(
                path: $reader->string('path', "{$key}.path"),
                basePath: $this->app->basePath(),
            ),
            'statsd' => new StatsdMetricReporter(
                host: $reader->string('host', "{$key}.host"),
                port: $reader->int('port', "{$key}.port"),
                prefix: $reader->optionalString('prefix', "{$key}.prefix", allowEmpty: true) ?? 'lpwork',
            ),
            default => throw new InvalidArgumentException("Unsupported metrics driver [{$reader->string('driver', "{$key}.driver")}]."),
        };
    }
}
