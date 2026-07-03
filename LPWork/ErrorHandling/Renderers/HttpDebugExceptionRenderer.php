<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Renderers;

use LPWork\DebugBar\DebugBarPageRenderer;
use LPWork\ErrorHandling\Contracts\HttpExceptionRenderer;
use LPWork\ErrorHandling\Debug\DebugExceptionInspector;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\Http\Contracts\HttpException;
use LPWork\Observability\DiagnosticsSnapshot;
use LPWork\Observability\DiagnosticsSnapshotFactory;
use LPWork\Responses\HttpResponse;
use Throwable;

/**
 * Renders http debug exception renderer output.
 */
final readonly class HttpDebugExceptionRenderer implements HttpExceptionRenderer
{
    /**
     * Creates a new HttpDebugExceptionRenderer instance.
     */
    public function __construct(
        private string $applicationPath,
        private ?HttpDebugContext $context = null,
        private ?DiagnosticsSnapshotFactory $snapshots = null,
        private DebugExceptionPageRenderer $pages = new DebugExceptionPageRenderer(),
        private ?DebugBarPageRenderer $debugBar = null,
    ) {}

    /**
     * Renders this component into its output representation.
     */
    public function render(Throwable $throwable): HttpResponse
    {
        $statusCode = $throwable instanceof HttpException ? $throwable->statusCode() : 500;
        $headers = $throwable instanceof HttpException ? $throwable->headers() : [];

        return HttpResponse::html(
            body: $this->renderDebugPage($throwable),
            statusCode: $statusCode,
            headers: $headers,
        );
    }

    private function renderDebugPage(Throwable $throwable): string
    {
        $this->context?->setThrowable($throwable);
        $snapshot = $this->snapshot();

        return $this->pages->render(
            new DebugExceptionInspector($this->applicationPath)->inspect($throwable),
            $snapshot,
            $this->debugBar?->render($snapshot) ?? '',
        );
    }

    private function snapshot(): DiagnosticsSnapshot
    {
        if ($this->snapshots !== null) {
            return $this->snapshots->make();
        }

        return new DiagnosticsSnapshot(
            groups: $this->context?->data() ?? [],
            metrics: [],
            logs: [],
        );
    }
}
