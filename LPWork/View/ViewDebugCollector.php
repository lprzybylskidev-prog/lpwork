<?php

declare(strict_types=1);

namespace LPWork\View;

use LPWork\Observability\Metric;
use LPWork\Observability\MetricCollector;

/**
 * Represents the view debug collector framework component.
 */
final class ViewDebugCollector
{
    /**
     * @var list<array<string, mixed>>
     */
    private array $renders = [];

    /**
     * Creates a new ViewDebugCollector instance.
     */
    public function __construct(
        private readonly ?MetricCollector $metrics = null,
    ) {}

    /**
     * @param list<string> $dataKeys
     * @param list<string> $sharedKeys
     * @param list<string> $sections
     */
    public function record(
        string $name,
        string $path,
        ?string $layout,
        array $dataKeys,
        array $sharedKeys,
        array $sections,
        bool $successful,
        float $durationMs,
        float $recordedAtMs = 0.0,
    ): void {
        $this->renders[] = [
            'Name' => $name,
            'Path' => $path,
            'Layout' => $layout,
            'Data keys' => $dataKeys,
            'Shared keys' => $sharedKeys,
            'Sections' => $sections,
            'Successful' => $successful,
            'Duration ms' => $durationMs,
        ];

        $this->metrics?->report(new Metric(
            name: 'views.rendered',
            value: $durationMs,
            unit: 'ms',
            tags: [
                'view' => $name,
                'successful' => $successful,
            ],
            recordedAtMs: $recordedAtMs,
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function renders(): array
    {
        return $this->renders;
    }

    /**
     * Resets this component to an empty lifecycle state.
     */
    public function reset(): void
    {
        $this->renders = [];
    }
}
