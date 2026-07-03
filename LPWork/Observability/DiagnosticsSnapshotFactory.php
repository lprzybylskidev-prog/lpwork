<?php

declare(strict_types=1);

namespace LPWork\Observability;

use LPWork\ErrorHandling\HttpDebugContext;

/**
 * Creates diagnostics snapshot factory instances from framework configuration.
 */
final readonly class DiagnosticsSnapshotFactory
{
    /**
     * Creates a new DiagnosticsSnapshotFactory instance.
     */
    public function __construct(
        private HttpDebugContext $context,
        private MetricCollector $metrics,
        private DiagnosticsCollector $diagnostics,
    ) {}

    /**
     * Builds or returns make.
     */
    public function make(): DiagnosticsSnapshot
    {
        return new DiagnosticsSnapshot(
            groups: $this->context->data(),
            metrics: $this->metrics->recent(),
            logs: $this->diagnostics->logs(),
        );
    }
}
