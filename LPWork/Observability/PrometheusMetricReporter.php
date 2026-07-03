<?php

declare(strict_types=1);

namespace LPWork\Observability;

use LPWork\Filesystem\Filesystem;
use LPWork\Observability\Contracts\MetricReporter;

/**
 * Represents the prometheus metric reporter framework component.
 */
final readonly class PrometheusMetricReporter implements MetricReporter
{
    /**
     * Creates a new PrometheusMetricReporter instance.
     */
    public function __construct(
        private string $path,
        private string $basePath,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Performs the report operation.
     */
    public function report(Metric $metric): void
    {
        $line = sprintf(
            "%s%s %s\n",
            preg_replace('/[^A-Za-z0-9_:]/', '_', $metric->name) ?? $metric->name,
            $this->labels($metric),
            $metric->value,
        );
        $this->filesystem->append($this->absolutePath(), $line);
    }

    private function labels(Metric $metric): string
    {
        if ($metric->tags === []) {
            return '';
        }

        $labels = [];

        foreach ($metric->tags as $key => $value) {
            if ($value === null) {
                continue;
            }

            $labels[] = sprintf('%s="%s"', preg_replace('/[^A-Za-z0-9_]/', '_', $key) ?? $key, addslashes((string) $value));
        }

        return $labels === [] ? '' : '{' . implode(',', $labels) . '}';
    }

    private function absolutePath(): string
    {
        if (str_starts_with($this->path, '/')) {
            return $this->path;
        }

        return rtrim($this->basePath, '/') . '/' . ltrim($this->path, '/');
    }
}
