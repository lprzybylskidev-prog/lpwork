<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Renderers;

use LPWork\ErrorHandling\Debug\DebugExceptionView;
use LPWork\ErrorHandling\Debug\DebugPreviousException;
use LPWork\ErrorHandling\Debug\DebugStackFrame;
use LPWork\Foundation\FrameworkMetadata;
use LPWork\Frontend\FrameworkAssets;
use LPWork\Observability\DiagnosticsSnapshot;
use LPWork\Observability\Metric;
use Stringable;

/**
 * Renders debug exception page renderer output.
 */
final readonly class DebugExceptionPageRenderer
{
    /**
     * Creates a new DebugExceptionPageRenderer instance.
     */
    public function __construct(
        private DebugContextValueRenderer $values = new DebugContextValueRenderer(),
        private PhpSourceHighlighter $sourceHighlighter = new PhpSourceHighlighter(),
        private FrameworkMetadata $metadata = new FrameworkMetadata(),
        private DebugExceptionDocumentRenderer $document = new DebugExceptionDocumentRenderer(),
    ) {}

    /**
     * Renders this component into its output representation.
     */
    public function render(DebugExceptionView $exception, DiagnosticsSnapshot $snapshot, string $debugBar = ''): string
    {
        return $this->document->render(
            $this->style(),
            $this->hero($exception, $snapshot),
            $this->workspace($exception, $snapshot),
            $this->script(),
            $debugBar,
        );
    }

    private function hero(DebugExceptionView $exception, DiagnosticsSnapshot $snapshot): string
    {
        return sprintf(
            '<header class="lp-debug-hero">
                <div class="lp-debug-brand-row">
                  %s
                  <button type="button" class="lp-debug-copy" data-lp-copy-exception="%s" data-lp-copy-label="Copy" data-lp-copy-done="Copied">Copy</button>
                </div>
                <div class="lp-debug-hero-grid">
                  <section class="lp-debug-impact">
                    <p>%s</p>
                    <h1>%s</h1>
                    <div class="lp-debug-class-line">%s</div>
                    <div class="lp-debug-message">%s</div>
                  </section>
                  <section class="lp-debug-facts" aria-label="Exception summary">
                    %s
                  </section>
                </div>
              </header>',
            FrameworkAssets::brand('Debug exception', 'lp-ui-framework-brand lp-debug-brand'),
            $this->escape($this->clipboardSummary($exception, $snapshot)),
            $this->escape($this->shortName($exception)),
            $this->escape($this->shortName($exception)),
            $this->escape($exception->name),
            $exception->message === ''
                ? '<span class="lp-debug-muted">No exception message.</span>'
                : $this->escape($exception->message),
            $this->facts($exception, $snapshot),
        );
    }

    private function facts(DebugExceptionView $exception, DiagnosticsSnapshot $snapshot): string
    {
        return sprintf(
            '<dl>
                <div><dt>Thrown at</dt><dd>%s</dd></div>
                <div><dt>Stack depth</dt><dd>%d frames</dd></div>
                <div><dt>Framework</dt><dd>LPWork %s</dd></div>
                <div><dt>PHP runtime</dt><dd>PHP %s</dd></div>
              </dl>',
            $this->escape($exception->file . ':' . (string) $exception->line),
            count($exception->frames),
            $this->escape($this->metadata->version()),
            $this->escape(PHP_VERSION),
        );
    }

    private function workspace(DebugExceptionView $exception, DiagnosticsSnapshot $snapshot): string
    {
        return sprintf(
            '<section class="lp-debug-workspace">
                <nav class="lp-debug-rail" aria-label="Debug sections">
                  %s
                </nav>
                <section class="lp-debug-stage">
                  <section class="lp-debug-panel is-active" data-lp-panel="overview">%s</section>
                  <section class="lp-debug-panel" data-lp-panel="trace">%s</section>
                  <section class="lp-debug-panel" data-lp-panel="previous">%s</section>
                  <section class="lp-debug-panel" data-lp-panel="request">%s</section>
                  <section class="lp-debug-panel" data-lp-panel="runtime">%s</section>
                </section>
              </section>',
            $this->rail($exception, $snapshot),
            $this->overview($exception, $snapshot),
            $this->trace($exception),
            $this->previous($exception),
            $this->request($snapshot->groups),
            $this->runtime($snapshot),
        );
    }

    private function rail(DebugExceptionView $exception, DiagnosticsSnapshot $snapshot): string
    {
        return sprintf(
            '<button type="button" class="lp-debug-nav is-active" data-lp-panel-tab="overview"><span>Overview</span><b>%s</b></button>
            <button type="button" class="lp-debug-nav" data-lp-panel-tab="trace"><span>Trace</span><b>%d</b></button>
            <button type="button" class="lp-debug-nav" data-lp-panel-tab="previous"><span>Previous</span><b>%d</b></button>
            <button type="button" class="lp-debug-nav" data-lp-panel-tab="request"><span>Request</span></button>
            <button type="button" class="lp-debug-nav" data-lp-panel-tab="runtime"><span>Runtime</span></button>',
            $this->shortName($exception),
            $exception->frameCounts['all'],
            count($exception->previousExceptions),
        );
    }

    private function overview(DebugExceptionView $exception, DiagnosticsSnapshot $snapshot): string
    {
        return sprintf(
            '<div class="lp-debug-section-heading">
                <p>Start here</p>
                <h2>What failed</h2>
                <span>The exception and request details most useful for the first read.</span>
              </div>
              <div class="lp-debug-overview-grid">
                <article class="lp-debug-card lp-debug-card-primary">
                  <h3>Failure point</h3>
                  %s
                </article>
                <article class="lp-debug-card">
                  <h3>Request snapshot</h3>
                  %s
                </article>
              </div>',
            $this->table([
                'Class' => $exception->name,
                'Message' => $exception->message === '' ? 'No exception message.' : $exception->message,
                'Source' => $this->exceptionSource($exception),
                'File' => $exception->file,
                'Line' => $exception->line,
            ]),
            $this->overviewRequest($snapshot->groups),
        );
    }

    private function exceptionSource(DebugExceptionView $exception): string
    {
        $frame = $exception->frames[0] ?? null;

        return $frame instanceof DebugStackFrame ? $frame->source : 'unknown';
    }

    /**
     * @param array<string, mixed> $groups
     */
    private function overviewRequest(array $groups): string
    {
        $request = is_array($groups['Request'] ?? null) ? $groups['Request'] : [];
        $route = is_array($groups['Route'] ?? null) ? $groups['Route'] : [];

        return $this->table([
            'Method' => $request['Method'] ?? 'unknown',
            'URL' => $request['URL'] ?? 'unknown',
            'Route' => $route['Name'] ?? 'unnamed',
            'Action' => $route['Action'] ?? 'unknown',
        ]);
    }

    private function trace(DebugExceptionView $exception): string
    {
        return sprintf(
            '<div class="lp-debug-section-heading">
                <p>Execution path</p>
                <h2>Trace and source</h2>
                <span>Pick a frame to inspect the surrounding code.</span>
              </div>
              <div class="lp-debug-trace-tools">%s</div>
              <div class="lp-debug-trace-grid">
                <div class="lp-debug-frame-list">%s</div>
                <div class="lp-debug-source-pane">%s</div>
              </div>',
            $this->frameFilters($exception),
            $this->frameList($exception->frames),
            $this->frameDetails($exception->frames),
        );
    }

    private function previous(DebugExceptionView $exception): string
    {
        return sprintf(
            '<div class="lp-debug-section-heading">
                <p>Exception chain</p>
                <h2>Previous exceptions</h2>
                <span>Exceptions caught and wrapped before the final exception reached the handler.</span>
              </div>
              <div class="lp-debug-trace-grid">
                <div class="lp-debug-frame-list">%s</div>
                <div class="lp-debug-source-pane">%s</div>
              </div>',
            $this->previousExceptionList($exception->previousExceptions),
            $this->previousExceptionDetails($exception->previousExceptions),
        );
    }

    /**
     * @param list<DebugPreviousException> $exceptions
     */
    private function previousExceptionList(array $exceptions): string
    {
        if ($exceptions === []) {
            return '<p class="lp-debug-empty">No previous exceptions recorded.</p>';
        }

        $html = '';

        foreach ($exceptions as $exception) {
            $active = $exception->index === 0 ? ' is-active' : '';
            $html .= sprintf(
                '<button type="button" class="lp-debug-frame%s" data-lp-previous="%d">
                    <span class="lp-debug-frame-number">#%d</span>
                    <span class="lp-debug-frame-body">
                      <span class="lp-debug-frame-label">%s</span>
                      <span class="lp-debug-frame-location">%s</span>
                    </span>
                    <span class="lp-debug-frame-source">%d frames</span>
                </button>',
                $active,
                $exception->index,
                $exception->index + 1,
                $this->escape($this->previousShortName($exception) . ': ' . $this->exceptionMessage($exception->message)),
                $this->escape($this->exceptionLocation($exception)),
                $exception->frameCounts['all'],
            );
        }

        return $html;
    }

    /**
     * @param list<DebugPreviousException> $exceptions
     */
    private function previousExceptionDetails(array $exceptions): string
    {
        if ($exceptions === []) {
            return '<article class="lp-debug-source-card is-active"><p class="lp-debug-empty">No previous exceptions recorded.</p></article>';
        }

        $html = '';

        foreach ($exceptions as $exception) {
            $active = $exception->index === 0 ? ' is-active' : '';
            $html .= sprintf(
                '<article class="lp-debug-source-card%s" data-lp-previous-detail="%d">
                    <header>
                      <div>
                        <p>previous #%d</p>
                        <h3>%s</h3>
                      </div>
                      <span>%s</span>
                    </header>
                    <div class="lp-debug-previous-summary">%s</div>
                    %s
                </article>',
                $active,
                $exception->index,
                $exception->index + 1,
                $this->escape($exception->name),
                $this->escape($this->exceptionLocation($exception)),
                $this->table([
                    'Class' => $exception->name,
                    'Message' => $this->exceptionMessage($exception->message),
                    'File' => $exception->file,
                    'Line' => $exception->line,
                    'Stack depth' => $exception->frameCounts['all'],
                ]),
                $this->previousSource($exception),
            );
        }

        return $html;
    }

    private function frameFilters(DebugExceptionView $exception): string
    {
        $labels = [
            'all' => 'All',
            'app' => 'App',
            'lpwork' => 'LPWork',
            'vendor' => 'Vendor',
            'other' => 'Other',
        ];
        $html = '';

        foreach ($labels as $source => $label) {
            $count = $exception->frameCounts[$source];

            if ($source !== 'all' && $count === 0) {
                continue;
            }

            $active = $source === 'all' ? ' is-active' : '';
            $html .= sprintf(
                '<button type="button" class="lp-debug-filter%s" data-lp-frame-filter="%s">%s <b>%d</b></button>',
                $active,
                $this->escape($source),
                $this->escape($label),
                $count,
            );
        }

        return $html;
    }

    /**
     * @param list<DebugStackFrame> $frames
     */
    private function frameList(array $frames): string
    {
        if ($frames === []) {
            return '<p class="lp-debug-empty">No stack frames recorded.</p>';
        }

        $html = '';

        foreach ($frames as $frame) {
            $active = $frame->index === 0 ? ' is-active' : '';
            $html .= sprintf(
                '<button type="button" class="lp-debug-frame%s" data-lp-frame="%d" data-lp-frame-source="%s">
                    <span class="lp-debug-frame-number">#%d</span>
                    <span class="lp-debug-frame-body">
                      <span class="lp-debug-frame-label">%s</span>
                      <span class="lp-debug-frame-location">%s</span>
                    </span>
                    <span class="lp-debug-frame-source">%s</span>
                </button>',
                $active,
                $frame->index,
                $this->escape($frame->source),
                $frame->index,
                $this->escape($frame->label),
                $this->escape($frame->location()),
                $this->escape($frame->source),
            );
        }

        return $html;
    }

    /**
     * @param list<DebugStackFrame> $frames
     */
    private function frameDetails(array $frames): string
    {
        if ($frames === []) {
            return '<article class="lp-debug-source-card"><p class="lp-debug-empty">No stack frames recorded.</p></article>';
        }

        $html = '';

        foreach ($frames as $frame) {
            $active = $frame->index === 0 ? ' is-active' : '';
            $html .= sprintf(
                '<article class="lp-debug-source-card%s" data-lp-frame-detail="%d">
                    <header>
                      <div>
                        <p>%s</p>
                        <h3>%s</h3>
                      </div>
                      <span>%s</span>
                    </header>
                    %s
                </article>',
                $active,
                $frame->index,
                $this->escape($frame->source),
                $this->escape($frame->label),
                $this->escape($frame->location()),
                $this->source($frame),
            );
        }

        return $html;
    }

    private function source(DebugStackFrame $frame): string
    {
        if ($frame->sourceLines === []) {
            return '<div class="lp-debug-card-body"><p class="lp-debug-empty">No readable source is available for this frame.</p></div>';
        }

        $html = '<pre class="lp-debug-source" tabindex="0"><code>';

        foreach ($frame->sourceLines as $line) {
            $active = $line['active'] ? ' is-active' : '';
            $html .= sprintf(
                '<span class="lp-debug-source-line%s"><span>%d</span><b>%s</b></span>',
                $active,
                $line['number'],
                $this->sourceHighlighter->highlight($line['code']),
            );
        }

        return $html . '</code></pre>';
    }

    private function previousSource(DebugPreviousException $exception): string
    {
        $frame = $exception->frames[0] ?? null;

        if (!$frame instanceof DebugStackFrame) {
            return '<div class="lp-debug-card-body"><p class="lp-debug-empty">No stack frames recorded for this previous exception.</p></div>';
        }

        return $this->source($frame);
    }

    private function previousShortName(DebugPreviousException $exception): string
    {
        return $exception->nameParts[count($exception->nameParts) - 1] ?? $exception->name;
    }

    private function exceptionMessage(string $message): string
    {
        return $message === '' ? 'No exception message.' : $message;
    }

    private function exceptionLocation(DebugPreviousException $exception): string
    {
        if ($exception->line === null) {
            return $exception->file;
        }

        return $exception->file . ':' . $exception->line;
    }

    /**
     * @param array<string, mixed> $groups
     */
    private function request(array $groups): string
    {
        if ($groups === []) {
            $groups = [
                'Debug context' => [
                    'Captured' => false,
                ],
            ];
        }

        $html = '<div class="lp-debug-section-heading"><p>Request state</p><h2>Framework context</h2><span>Request, route, middleware, session and module data captured at failure time.</span></div><div class="lp-debug-context-grid">';

        foreach ($groups as $name => $data) {
            $html .= sprintf(
                '<article class="lp-debug-card"><h3>%s</h3>%s</article>',
                $this->escape($name),
                $this->contextGroupContent($name, $data),
            );
        }

        return $html . '</div>';
    }

    private function contextGroupContent(string $name, mixed $data): string
    {
        return match ($name) {
            'Events' => $this->listContextContent($data, 'No events recorded.'),
            'Database' => $this->namedListContextContent($data, 'Queries', 'No database queries recorded.'),
            'Views' => $this->namedListContextContent($data, 'Renders', 'No views rendered.'),
            'Cache' => $this->namedListContextContent($data, 'Operations', 'No cache operations recorded.'),
            'Queue' => $this->namedListContextContent($data, 'Jobs', 'No queue jobs recorded.'),
            'Scheduler' => $this->namedListContextContent($data, 'Tasks', 'No scheduler tasks recorded.'),
            'Security' => $this->namedListContextContent($data, 'Denials', 'No security denials recorded.'),
            'Throttle' => $this->namedListContextContent($data, 'Denials', 'No throttled requests recorded.'),
            default => $this->table((array) $data),
        };
    }

    private function listContextContent(mixed $data, string $emptyMessage): string
    {
        if (!is_array($data) || $data === []) {
            return '<p class="lp-debug-empty">' . $this->escape($emptyMessage) . '</p>';
        }

        return $this->table($data);
    }

    private function namedListContextContent(mixed $data, string $key, string $emptyMessage): string
    {
        if (!is_array($data)) {
            return '<p class="lp-debug-empty">' . $this->escape($emptyMessage) . '</p>';
        }

        $items = $data[$key] ?? null;

        if (!is_array($items) || $items === []) {
            return '<p class="lp-debug-empty">' . $this->escape($emptyMessage) . '</p>';
        }

        return $this->table($data);
    }

    private function runtime(DiagnosticsSnapshot $snapshot): string
    {
        return '<div class="lp-debug-section-heading"><p>Runtime</p><h2>Diagnostics</h2><span>Metrics and logs recorded before this debug page was rendered.</span></div>'
            . '<div class="lp-debug-runtime-grid">'
            . '<article class="lp-debug-card lp-debug-card-wide"><h3>Process</h3>' . $this->table([
                'Peak memory' => $this->formatBytes(memory_get_peak_usage(true)),
                'Framework version' => 'LPWork ' . $this->metadata->version(),
                'PHP version' => PHP_VERSION,
            ]) . '</article>'
            . '<article class="lp-debug-card lp-debug-card-wide"><h3>Metrics</h3>' . $this->metrics($snapshot->metrics) . '</article>'
            . '<article class="lp-debug-card lp-debug-card-wide"><h3>Logs</h3>' . $this->logs($snapshot->logs) . '</article>'
            . '</div>';
    }

    /**
     * @param list<Metric> $metrics
     */
    private function metrics(array $metrics): string
    {
        if ($metrics === []) {
            return '<p class="lp-debug-empty">No metrics were recorded before the exception page was rendered.</p>';
        }

        $rows = [];

        foreach ($metrics as $metric) {
            $rows[] = [
                'Name' => $metric->name,
                'Value' => (string) $metric->value,
                'Unit' => $metric->unit,
                'Recorded at ms' => $this->number($metric->recordedAtMs),
                'Tags' => $metric->tags,
            ];
        }

        return $this->records($rows);
    }

    /**
     * @param list<array{channel: string, level: string, message: string, context: array<string, mixed>}> $logs
     */
    private function logs(array $logs): string
    {
        if ($logs === []) {
            return '<p class="lp-debug-empty">No application logs were recorded before the exception page was rendered.</p>';
        }

        $rows = [];

        foreach ($logs as $log) {
            $rows[] = [
                'Level' => $log['level'],
                'Message' => $log['message'],
                'Channel' => $log['channel'],
                'Context' => $log['context'],
            ];
        }

        return $this->records($rows);
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    private function records(array $records): string
    {
        return $this->values->records($records);
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function table(array $data): string
    {
        return $this->values->table($data);
    }

    private function shortName(DebugExceptionView $exception): string
    {
        return $exception->nameParts[count($exception->nameParts) - 1] ?? $exception->name;
    }

    private function clipboardSummary(DebugExceptionView $exception, DiagnosticsSnapshot $snapshot): string
    {
        $request = is_array($snapshot->groups['Request'] ?? null) ? $snapshot->groups['Request'] : [];
        $route = is_array($snapshot->groups['Route'] ?? null) ? $snapshot->groups['Route'] : [];
        $lines = [
            '# LPWork Debug Exception',
            '',
            '## Exception',
            'Class: ' . $exception->name,
            'Message: ' . $this->exceptionMessage($exception->message),
            'Source: ' . $this->exceptionSource($exception),
            'Thrown at: ' . $exception->file . ':' . (string) $exception->line,
            'Stack depth: ' . (string) count($exception->frames) . ' frames',
            'Framework: LPWork ' . $this->metadata->version(),
            'PHP: ' . PHP_VERSION,
            '',
            '## Request',
            'Method: ' . $this->plainValue($request['Method'] ?? 'unknown'),
            'URL: ' . $this->plainValue($request['URL'] ?? 'unknown'),
            'Route: ' . $this->plainValue($route['Name'] ?? 'unnamed'),
            'Action: ' . $this->plainValue($route['Action'] ?? 'unknown'),
            '',
            '## Top Frames',
            ...$this->clipboardFrames($exception->frames),
        ];

        if ($exception->previousExceptions !== []) {
            $lines[] = '';
            $lines[] = '## Previous Exceptions';
            foreach (array_slice($exception->previousExceptions, 0, 5) as $previous) {
                $lines[] = sprintf(
                    '- #%d %s: %s at %s (%d frames)',
                    $previous->index + 1,
                    $previous->name,
                    $this->exceptionMessage($previous->message),
                    $this->exceptionLocation($previous),
                    $previous->frameCounts['all'],
                );
            }
        }

        $context = $this->clipboardContext($snapshot->groups);
        if ($context !== []) {
            $lines[] = '';
            $lines[] = '## Framework Context';
            array_push($lines, ...$context);
        }

        $diagnostics = $this->clipboardDiagnostics($snapshot);
        if ($diagnostics !== []) {
            $lines[] = '';
            $lines[] = '## Diagnostics';
            array_push($lines, ...$diagnostics);
        }

        return implode("\n", $lines);
    }

    /**
     * @param list<DebugStackFrame> $frames
     *
     * @return list<string>
     */
    private function clipboardFrames(array $frames): array
    {
        if ($frames === []) {
            return ['No stack frames recorded.'];
        }

        $lines = [];

        foreach (array_slice($frames, 0, 8) as $frame) {
            $lines[] = sprintf(
                '- #%d [%s] %s at %s',
                $frame->index,
                $frame->source,
                $frame->label,
                $frame->location(),
            );
        }

        if (count($frames) > 8) {
            $lines[] = sprintf('- ... %d more frames omitted.', count($frames) - 8);
        }

        return $lines;
    }

    /**
     * @param array<string, mixed> $groups
     *
     * @return list<string>
     */
    private function clipboardContext(array $groups): array
    {
        $lines = [];

        foreach ($groups as $name => $data) {
            if (!is_array($data)) {
                $lines[] = '- ' . $name . ': ' . $this->plainValue($data);
                continue;
            }

            $lines[] = '- ' . $name . ':';
            $shown = 0;

            foreach ($data as $key => $value) {
                if ($shown >= 8) {
                    $lines[] = sprintf('  - ... %d more fields omitted.', count($data) - $shown);
                    break;
                }

                $lines[] = '  - ' . (string) $key . ': ' . $this->plainValue($value);
                ++$shown;
            }
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function clipboardDiagnostics(DiagnosticsSnapshot $snapshot): array
    {
        $lines = [
            '- Peak memory: ' . $this->formatBytes(memory_get_peak_usage(true)),
        ];

        if ($snapshot->metrics !== []) {
            $lines[] = '- Metrics:';
            foreach (array_slice($snapshot->metrics, 0, 8) as $metric) {
                $lines[] = sprintf(
                    '  - %s=%s %s at +%sms tags=%s',
                    $metric->name,
                    $this->number($metric->value),
                    $metric->unit,
                    $this->number($metric->recordedAtMs),
                    $this->plainValue($metric->tags),
                );
            }
        }

        if ($snapshot->logs !== []) {
            $lines[] = '- Logs:';
            foreach (array_slice($snapshot->logs, 0, 5) as $log) {
                $lines[] = sprintf(
                    '  - [%s] %s: %s context=%s',
                    $log['level'],
                    $log['channel'],
                    $log['message'],
                    $this->plainValue($log['context']),
                );
            }
        }

        return $lines;
    }

    private function plainValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return $this->truncate((string) $value);
        }

        if ($value instanceof Stringable) {
            return $this->truncate((string) $value);
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            return get_debug_type($value);
        }

        return $this->truncate($encoded);
    }

    private function truncate(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        if (strlen($value) <= 360) {
            return $value;
        }

        return substr($value, 0, 357) . '...';
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return $this->number($bytes / 1024 / 1024) . ' MB';
        }

        if ($bytes >= 1024) {
            return $this->number($bytes / 1024) . ' KB';
        }

        return $bytes . ' B';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function style(): string
    {
        return <<<'CSS'
            .lp-debug-page {
              background: #080d13;
              color: #edf2f7;
              font: 14px Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            .lp-debug-shell {
              display: flex;
              flex-direction: column;
              gap: 18px;
              margin: 0 auto;
              max-width: 1520px;
              min-width: 0;
              padding: 18px;
              width: 100%;
            }

            .lp-debug-hero {
              background: #0d141d;
              border: 1px solid #263241;
              border-radius: 8px;
              box-shadow: 0 24px 80px rgba(0, 0, 0, .48);
              display: grid;
              gap: 0;
              grid-template-columns: minmax(0, 1.35fr) minmax(320px, .75fr);
              max-width: 100%;
              min-width: 0;
              overflow: hidden;
            }

            .lp-debug-brand-row,
            .lp-debug-brand,
            .lp-debug-hero-grid,
            .lp-debug-workspace,
            .lp-debug-section-heading {
              display: grid;
            }

            .lp-debug-brand-row {
              align-items: center;
              background: #0b1118;
              border-bottom: 1px solid #263241;
              gap: 12px;
              grid-column: 1;
              grid-template-columns: minmax(0, 1fr) auto;
              min-height: 58px;
              padding: 12px 14px;
            }

            .lp-debug-brand {
              gap: 10px;
            }

            .lp-debug-brand img {
              height: 28px;
              width: 28px;
            }

            .lp-debug-copy {
              align-items: center;
              background: rgba(66, 136, 206, .15);
              border: 1px solid rgba(101, 169, 237, .42);
              border-radius: 6px;
              color: var(--lp-ui-blue-strong);
              cursor: pointer;
              display: inline-flex;
              font: inherit;
              font-size: 12px;
              font-weight: 800;
              justify-content: center;
              min-height: 34px;
              padding: 8px 11px;
            }

            .lp-debug-copy:hover,
            .lp-debug-copy:focus {
              background: rgba(66, 136, 206, .24);
              color: #ffffff;
              outline: 0;
            }

            .lp-debug-copy.is-copied {
              border-color: rgba(134, 239, 172, .48);
              color: #86efac;
            }

            .lp-debug-copy.is-failed {
              border-color: rgba(248, 113, 113, .48);
              color: #fecaca;
            }

            .lp-debug-facts dd,
            .lp-debug-frame-number,
            .lp-debug-frame-source {
              font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            }

            .lp-debug-hero-grid {
              align-items: center;
              display: contents;
              gap: 20px;
            }

            .lp-debug-facts {
              align-self: stretch;
              background: #080d13;
              border-left: 1px solid #263241;
              grid-column: 2;
              grid-row: 1 / span 2;
              min-width: 0;
              overflow: hidden;
              padding: 12px;
            }

            .lp-debug-impact {
              min-width: 0;
              overflow: hidden;
              padding: 22px 20px 20px;
            }

            .lp-debug-impact p,
            .lp-debug-section-heading p,
            .lp-debug-source-card header p {
              color: var(--lp-ui-blue-strong);
              font-size: 12px;
              font-weight: 800;
              letter-spacing: .08em;
              margin: 0;
              text-transform: uppercase;
            }

            .lp-debug-impact h1 {
              font-size: clamp(38px, 5.4vw, 70px);
              letter-spacing: 0;
              line-height: 1.02;
              margin: 8px 0 10px;
              overflow-wrap: anywhere;
            }

            .lp-debug-class-line {
              color: #94a3b8;
              font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
              font-size: 13px;
              line-height: 1.45;
              margin-bottom: 16px;
              overflow-wrap: anywhere;
            }

            .lp-debug-message {
              background: rgba(248, 113, 113, .11);
              border: 1px solid rgba(248, 113, 113, .24);
              border-left: 3px solid #f87171;
              color: #fecaca;
              font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
              line-height: 1.65;
              max-width: 100%;
              padding: 12px 14px;
              overflow-wrap: anywhere;
            }

            .lp-debug-facts dl {
              align-content: center;
              display: grid;
              gap: 7px;
              margin: 0;
              min-height: 100%;
            }

            .lp-debug-facts div,
            .lp-debug-source-card,
            .lp-debug-rail,
            .lp-debug-stage {
              background: #0d141d;
              border: 1px solid #263241;
              border-radius: 8px;
            }

            .lp-debug-facts div {
              background: #0d141d;
              display: grid;
              gap: 3px;
              padding: 11px 12px;
            }

            .lp-debug-facts dt,
            .lp-debug-muted,
            .lp-debug-empty,
            .lp-debug-record summary small,
            .lp-debug-section-heading span,
            .lp-debug-frame-location {
              color: #94a3b8;
            }

            .lp-debug-facts dt {
              font-size: 12px;
            }

            .lp-debug-facts dd {
              margin: 0;
              overflow-wrap: anywhere;
            }

            .lp-debug-workspace {
              align-items: start;
              flex: 0 0 auto;
              gap: 18px;
              grid-template-columns: 220px minmax(0, 1fr);
              max-width: 100%;
              min-width: 0;
            }

            .lp-debug-rail {
              background: #080d13;
              align-content: start;
              display: grid;
              gap: 3px;
              padding: 10px;
              position: sticky;
              top: 18px;
              min-width: 0;
            }

            .lp-debug-nav,
            .lp-debug-filter,
            .lp-debug-frame {
              background: transparent;
              border: 1px solid transparent;
              border-radius: 6px;
              color: #cbd5e1;
              cursor: pointer;
              font: inherit;
            }

            .lp-debug-nav {
              align-items: center;
              display: grid;
              gap: 8px;
              grid-template-columns: minmax(0, 1fr) auto;
              min-height: 46px;
              min-width: 0;
              padding: 10px 12px;
              text-align: left;
            }

            .lp-debug-nav b,
            .lp-debug-filter b {
              color: var(--lp-ui-blue-strong);
              font-size: 12px;
              font-weight: 800;
              min-width: 0;
              overflow-wrap: anywhere;
            }

            .lp-debug-nav:hover,
            .lp-debug-filter:hover,
            .lp-debug-filter.is-active,
            .lp-debug-frame:hover,
            .lp-debug-frame.is-active {
              background: #121b26;
              border-color: #2c3a4c;
              color: #ffffff;
            }

            .lp-debug-nav.is-active {
              background: #172231;
              border-color: #3c5773;
              color: #ffffff;
            }

            .lp-debug-stage {
              background: #0d141d;
              display: block;
              max-width: 100%;
              min-width: 0;
              padding: 12px;
            }

            .lp-debug-panel,
            .lp-debug-source-card {
              display: none;
              max-width: 100%;
              min-width: 0;
            }

            .lp-debug-panel.is-active,
            .lp-debug-source-card.is-active {
              display: block;
            }

            .lp-debug-panel.is-active {
              overflow: visible;
            }

            .lp-debug-section-heading {
              align-items: start;
              gap: 7px;
              grid-template-columns: 1fr;
              margin-bottom: 14px;
            }

            .lp-debug-section-heading h2 {
              font-size: 24px;
              letter-spacing: 0;
              margin: 4px 0 0;
            }

            .lp-debug-overview-grid,
            .lp-debug-context-grid,
            .lp-debug-runtime-grid {
              display: grid;
              gap: 12px;
              max-width: 100%;
              min-width: 0;
              overflow: hidden;
            }

            .lp-debug-overview-grid {
              grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .lp-debug-runtime-grid {
              grid-template-columns: minmax(260px, .6fr) minmax(0, 1.4fr);
            }

            .lp-debug-card-wide {
              grid-column: 1 / -1;
            }

            .lp-debug-card {
              background: #080d13;
              border: 1px solid #263241;
              border-radius: 7px;
              max-width: 100%;
              min-width: 0;
              overflow: hidden;
              padding: 0;
            }

            .lp-debug-card-primary {
              border-color: rgba(248, 113, 113, .45);
            }

            .lp-debug-card h3 {
              background: #111a25;
              border-bottom: 1px solid #263241;
              font-size: 15px;
              letter-spacing: 0;
              margin: 0;
              padding: 12px 14px;
            }

            .lp-debug-card > .lp-debug-data-card,
            .lp-debug-card > .lp-debug-records {
              border: 0;
              border-radius: 0;
            }

            .lp-debug-trace-tools {
              display: flex;
              flex-wrap: wrap;
              gap: 8px;
              margin-bottom: 12px;
            }

            .lp-debug-filter {
              padding: 8px 10px;
            }

            .lp-debug-trace-grid {
              display: grid;
              gap: 12px;
              grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
              max-width: 100%;
              min-height: 0;
              min-width: 0;
              overflow: hidden;
            }

            .lp-debug-source-pane {
              max-width: 100%;
              min-width: 0;
              overflow: hidden;
            }

            .lp-debug-frame-list {
              align-content: start;
              align-items: start;
              display: grid;
              gap: 8px;
              max-height: 720px;
              min-width: 0;
              overflow: auto;
            }

            .lp-debug-frame {
              align-items: center;
              display: grid;
              gap: 10px;
              grid-template-columns: auto minmax(0, 1fr) auto;
              max-width: 100%;
              min-width: 0;
              min-height: 66px;
              padding: 10px;
              text-align: left;
            }

            .lp-debug-frame[hidden] {
              display: none;
            }

            .lp-debug-frame-body {
              display: grid;
              gap: 4px;
              min-width: 0;
            }

            .lp-debug-frame-label,
            .lp-debug-frame-location {
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
            }

            .lp-debug-frame-number,
            .lp-debug-frame-source {
              color: var(--lp-ui-blue-strong);
              font-size: 12px;
            }

            .lp-debug-source-card {
              max-width: 100%;
              min-width: 0;
              overflow: hidden;
            }

            .lp-debug-source-card header {
              align-items: center;
              background: #111a25;
              border-bottom: 1px solid #263241;
              display: grid;
              gap: 12px;
              grid-template-columns: minmax(0, 1fr) auto;
              padding: 14px;
            }

            .lp-debug-source-card h3 {
              font-size: 15px;
              letter-spacing: 0;
              margin: 4px 0 0;
              overflow-wrap: anywhere;
            }

            .lp-debug-source-card header span {
              color: #94a3b8;
              font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
              font-size: 12px;
              overflow-wrap: anywhere;
              text-align: right;
            }

            .lp-debug-source {
              background: #080d13;
              max-width: 100%;
              margin: 0;
              overflow: auto;
              padding: 12px 0;
            }

            .lp-debug-previous-summary {
              border-bottom: 1px solid #263241;
              max-width: 100%;
              min-width: 0;
              overflow: auto;
              padding: 12px;
            }

            .lp-debug-source-line {
              display: grid;
              grid-template-columns: 64px minmax(0, 1fr);
              min-height: 22px;
            }

            .lp-debug-source-line > span {
              color: #64748b;
              padding-right: 14px;
              text-align: right;
              user-select: none;
            }

            .lp-debug-source-line > b {
              color: #dbeafe;
              font-weight: 400;
              min-width: max-content;
              padding-right: 18px;
              white-space: pre;
            }

            .lp-debug-source-line.is-active {
              background: rgba(248, 113, 113, .16);
            }

            .lp-src-keyword { color: #f472b6; }
            .lp-src-name { color: #93c5fd; }
            .lp-src-variable { color: var(--lp-ui-blue-strong); }
            .lp-src-string { color: #fbbf24; }
            .lp-src-number { color: #c4b5fd; }
            .lp-src-comment { color: #64748b; font-style: italic; }
            .lp-src-tag { color: #f472b6; }
            .lp-src-punctuation { color: #cbd5e1; }
            .lp-src-text { color: #dbeafe; }

            .lp-debug-code,
            .lp-debug-token,
            .lp-debug-record summary small,
            .lp-debug-field-group > header small,
            .lp-debug-inline-record > header span,
            .lp-debug-nested-row > span,
            .lp-debug-tree-key,
            .lp-debug-tree-meta {
              font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
              overflow-wrap: anywhere;
            }

            .lp-debug-fields {
              display: grid;
              gap: 1px;
            }

            .lp-debug-data-card {
              background: #080d13;
              border: 1px solid #263241;
              border-radius: 7px;
              display: grid;
              max-width: 100%;
              min-width: 0;
              overflow: hidden;
            }

            .lp-debug-data-head {
              align-items: center;
              background: #111a25;
              border-bottom: 1px solid #263241;
              color: #93a4b8;
              display: grid;
              font-size: 11px;
              font-weight: 900;
              gap: 12px;
              grid-template-columns: minmax(190px, 280px) minmax(0, 1fr);
              letter-spacing: .06em;
              min-height: 34px;
              text-transform: uppercase;
            }

            .lp-debug-data-head span {
              padding: 0 12px;
            }

            .lp-debug-empty {
              color: #94a3b8;
              margin: 0;
              padding: 14px;
            }

            .lp-debug-field {
              background: #0d141d;
              display: grid;
              grid-template-columns: minmax(190px, 280px) minmax(0, 1fr);
              max-width: 100%;
              min-width: 0;
            }

            .lp-debug-field + .lp-debug-field,
            .lp-debug-field-group + .lp-debug-field,
            .lp-debug-field + .lp-debug-field-group,
            .lp-debug-field-group + .lp-debug-field-group {
              border-top: 1px solid rgba(255, 255, 255, .055);
            }

            .lp-debug-field-name {
              background: #0a1017;
              color: #9fb0c4;
              font-weight: 800;
              line-height: 1.45;
              overflow-wrap: anywhere;
              padding: 10px 12px;
            }

            .lp-debug-field-value {
              color: #edf2f7;
              min-width: 0;
              padding: 10px 12px;
            }

            .lp-debug-field-group {
              background: #0a1017;
              display: grid;
              gap: 10px;
              padding: 12px;
            }

            .lp-debug-field-group > header,
            .lp-debug-inline-record > header {
              align-items: center;
              display: flex;
              gap: 12px;
              justify-content: space-between;
            }

            .lp-debug-field-group > header span {
              color: #d8e4f3;
              font-weight: 900;
            }

            .lp-debug-field-group > header small,
            .lp-debug-inline-record > header small {
              color: #94a3b8;
              font-size: 11px;
            }

            .lp-debug-code {
              background: #081018;
              border: 1px solid rgba(255, 255, 255, .075);
              border-radius: 5px;
              color: #e7eef8;
              display: block;
              font-size: 12px;
              line-height: 1.5;
              max-width: 100%;
              padding: 7px 8px;
              white-space: pre-wrap;
              word-break: break-word;
            }

            .lp-debug-code.is-number {
              color: #8fc7ff;
            }

            .lp-debug-token-list {
              display: flex;
              flex-wrap: wrap;
              gap: 6px;
            }

            .lp-debug-token {
              background: #0a1722;
              border: 1px solid #26394d;
              border-radius: 5px;
              color: #b9d8f7;
              display: inline-flex;
              font-size: 12px;
              line-height: 1;
              padding: 5px 7px;
            }

            .lp-debug-token.is-true {
              color: #86efac;
            }

            .lp-debug-token.is-false {
              color: #fca5a5;
            }

            .lp-debug-nested {
              background: #081018;
              border: 1px solid rgba(255, 255, 255, .075);
              border-radius: 6px;
              display: grid;
              max-width: 100%;
              min-width: 0;
              overflow: hidden;
            }

            .lp-debug-nested-row {
              display: grid;
              grid-template-columns: minmax(130px, 190px) minmax(0, 1fr);
            }

            .lp-debug-nested-row + .lp-debug-nested-row {
              border-top: 1px solid rgba(255, 255, 255, .06);
            }

            .lp-debug-nested-row > span {
              background: #0b121b;
              color: #9fb0c4;
              font-weight: 800;
              padding: 9px 10px;
            }

            .lp-debug-nested-row > div {
              min-width: 0;
              padding: 9px 10px;
            }

            .lp-debug-tree {
              background: #081018;
              border: 1px solid rgba(255, 255, 255, .075);
              border-radius: 6px;
              display: grid;
              max-width: 100%;
              min-width: 0;
              overflow: hidden;
            }

            .lp-debug-tree-node {
              min-width: 0;
            }

            .lp-debug-tree-node + .lp-debug-tree-node {
              border-top: 1px solid rgba(255, 255, 255, .055);
            }

            .lp-debug-tree-node summary {
              cursor: pointer;
              list-style: none;
            }

            .lp-debug-tree-node summary::-webkit-details-marker {
              display: none;
            }

            .lp-debug-tree-row {
              align-items: center;
              background: #0d141d;
              display: grid;
              gap: 8px;
              grid-template-columns: 18px minmax(120px, 190px) minmax(0, 1fr);
              min-width: 0;
              padding: 7px 10px;
            }

            .lp-debug-tree-node.is-branch > summary .lp-debug-tree-row {
              background: #111a25;
            }

            .lp-debug-tree-node.is-branch > summary:hover .lp-debug-tree-row {
              background: #142030;
            }

            .lp-debug-tree-node.is-leaf > .lp-debug-tree-row {
              background: #0a1017;
            }

            .lp-debug-tree-toggle {
              border-bottom: 2px solid var(--lp-ui-blue-strong);
              border-right: 2px solid var(--lp-ui-blue-strong);
              height: 7px;
              justify-self: center;
              transform: rotate(-45deg);
              transition: transform .12s ease;
              width: 7px;
            }

            .lp-debug-tree-node[open] > summary .lp-debug-tree-toggle {
              transform: rotate(45deg);
            }

            .lp-debug-tree-spacer {
              height: 7px;
              width: 7px;
            }

            .lp-debug-tree-key {
              color: #c8d4e2;
              font-size: 12px;
              font-weight: 900;
              min-width: 0;
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
            }

            .lp-debug-tree-key.is-root {
              color: #8090a3;
            }

            .lp-debug-tree-value {
              align-items: center;
              display: flex;
              flex-wrap: wrap;
              gap: 7px;
              min-width: 0;
            }

            .lp-debug-tree-value > .lp-debug-code {
              display: inline-block;
              max-width: 100%;
              padding: 3px 6px;
            }

            .lp-debug-tree-summary {
              color: #e7eef8;
              font-weight: 900;
            }

            .lp-debug-tree-details {
              align-items: center;
              display: inline-flex;
              flex-wrap: wrap;
              gap: 7px;
            }

            .lp-debug-tree-meta {
              color: #94a3b8;
              font-size: 12px;
            }

            .lp-debug-tree-children {
              border-left: 1px solid rgba(101, 169, 237, .28);
              display: grid;
              margin-left: 20px;
            }

            .lp-debug-inline-records {
              display: grid;
              gap: 8px;
              max-width: 100%;
              min-width: 0;
            }

            .lp-debug-inline-record {
              background: #0d141d;
              border: 1px solid rgba(255, 255, 255, .075);
              border-radius: 6px;
              max-width: 100%;
              min-width: 0;
              overflow: hidden;
            }

            .lp-debug-inline-record > header {
              background: #081018;
              border-bottom: 1px solid rgba(255, 255, 255, .06);
              padding: 9px 10px;
            }

            .lp-debug-inline-record > header span {
              color: var(--lp-ui-blue-strong);
              font-size: 11px;
              font-weight: 900;
            }

            .lp-debug-records {
              display: grid;
              gap: 10px;
              max-width: 100%;
              min-width: 0;
              overflow: auto;
              padding: 10px;
            }

            .lp-debug-record {
              background: #0a1017;
              border: 1px solid #263241;
              border-radius: 7px;
              max-width: 100%;
              min-width: 0;
              overflow: hidden;
            }

            .lp-debug-record > summary {
              align-items: center;
              cursor: pointer;
              display: grid;
              gap: 12px;
              grid-template-columns: minmax(0, 1fr) auto auto;
              list-style: none;
              padding: 12px 14px;
            }

            .lp-debug-record > summary:hover {
              background: #101a25;
            }

            .lp-debug-record > summary span {
              font-weight: 800;
              min-width: 0;
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
            }

            .lp-debug-record > summary::-webkit-details-marker {
              display: none;
            }

            .lp-debug-record > summary::after {
              border-bottom: 2px solid var(--lp-ui-blue-strong);
              border-right: 2px solid var(--lp-ui-blue-strong);
              content: "";
              height: 7px;
              transform: rotate(-45deg);
              transition: transform .12s ease;
              width: 7px;
            }

            .lp-debug-record[open] > summary::after {
              transform: rotate(45deg);
            }

            .lp-debug-record > .lp-debug-data-card {
              border: 0;
              border-radius: 0;
              border-top: 1px solid #263241;
            }

            .lp-debug-record > .lp-debug-data-card > .lp-debug-data-head {
              display: none;
            }

            .lp-debug-card-body {
              padding: 14px;
            }

            @media (max-width: 1080px) {
              .lp-debug-hero {
                grid-template-columns: 1fr;
              }

              .lp-debug-hero-grid {
                display: grid;
              }

              .lp-debug-brand-row,
              .lp-debug-facts {
                grid-column: auto;
                grid-row: auto;
              }

              .lp-debug-hero-grid,
              .lp-debug-workspace,
              .lp-debug-trace-grid,
              .lp-debug-overview-grid,
              .lp-debug-runtime-grid {
                grid-template-columns: 1fr;
              }

              .lp-debug-workspace {
                grid-template-rows: auto auto;
              }

              .lp-debug-rail {
                display: flex;
                flex-wrap: wrap;
                position: static;
              }

              .lp-debug-nav {
                flex: 1 1 132px;
                grid-template-columns: 1fr;
                min-width: 132px;
                white-space: normal;
              }
            }

            @media (max-width: 680px) {
              .lp-debug-shell { padding: 10px; }
              .lp-debug-brand-row { min-height: 48px; }
              .lp-debug-impact { padding: 16px; }
              .lp-debug-impact h1 { font-size: 34px; }
              .lp-debug-message { padding: 10px 12px; }
              .lp-debug-facts { border-left: 0; border-top: 1px solid #263241; padding: 10px; }
              .lp-debug-section-heading,
              .lp-debug-source-card header {
                grid-template-columns: 1fr;
              }
              .lp-debug-source-card header span { text-align: left; }
              .lp-debug-field,
              .lp-debug-nested-row {
                grid-template-columns: 1fr;
              }
              .lp-debug-tree-row {
                grid-template-columns: 18px minmax(88px, 36%) minmax(0, 1fr);
                padding: 7px 8px;
              }
              .lp-debug-tree-children {
                margin-left: 14px;
              }
              .lp-debug-field-name,
              .lp-debug-nested-row > span {
                padding-bottom: 4px;
              }
              .lp-debug-field-value,
              .lp-debug-nested-row > div {
                padding-top: 4px;
              }
              .lp-debug-record > summary {
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 4px;
              }
              .lp-debug-record > summary small {
                grid-column: 1 / -1;
                white-space: normal;
              }
            }
            CSS;
    }

    private function script(): string
    {
        return <<<'JS'
            (() => {
              const activate = (selector, current, attr, value) => {
                document.querySelectorAll(selector).forEach((element) => {
                  element.classList.toggle('is-active', element.getAttribute(attr) === value);
                });
                current?.classList.add('is-active');
              };

              const copyText = async (text) => {
                if (navigator.clipboard?.writeText) {
                  await navigator.clipboard.writeText(text);
                  return;
                }

                const field = document.createElement('textarea');
                field.value = text;
                field.setAttribute('readonly', 'readonly');
                field.style.position = 'fixed';
                field.style.left = '-9999px';
                document.body.appendChild(field);
                field.select();

                try {
                  document.execCommand('copy');
                } finally {
                  field.remove();
                }
              };

              document.querySelectorAll('[data-lp-panel-tab]').forEach((button) => {
                button.addEventListener('click', () => {
                  const panel = button.getAttribute('data-lp-panel-tab');
                  activate('[data-lp-panel-tab]', button, 'data-lp-panel-tab', panel);
                  document.querySelectorAll('[data-lp-panel]').forEach((element) => {
                    element.classList.toggle('is-active', element.getAttribute('data-lp-panel') === panel);
                  });
                });
              });

              document.querySelectorAll('[data-lp-frame]').forEach((button) => {
                button.addEventListener('click', () => {
                  const frame = button.getAttribute('data-lp-frame');
                  activate('[data-lp-frame]', button, 'data-lp-frame', frame);
                  document.querySelectorAll('[data-lp-frame-detail]').forEach((element) => {
                    element.classList.toggle('is-active', element.getAttribute('data-lp-frame-detail') === frame);
                  });
                  document.querySelector('[data-lp-panel-tab="trace"]')?.click();
                });
              });

              document.querySelectorAll('[data-lp-previous]').forEach((button) => {
                button.addEventListener('click', () => {
                  const previous = button.getAttribute('data-lp-previous');
                  activate('[data-lp-previous]', button, 'data-lp-previous', previous);
                  document.querySelectorAll('[data-lp-previous-detail]').forEach((element) => {
                    element.classList.toggle('is-active', element.getAttribute('data-lp-previous-detail') === previous);
                  });
                  document.querySelector('[data-lp-panel-tab="previous"]')?.click();
                });
              });

              document.querySelectorAll('[data-lp-frame-filter]').forEach((button) => {
                button.addEventListener('click', () => {
                  const filter = button.getAttribute('data-lp-frame-filter') ?? 'all';
                  activate('[data-lp-frame-filter]', button, 'data-lp-frame-filter', filter);
                  document.querySelectorAll('[data-lp-frame]').forEach((frame) => {
                    const source = frame.getAttribute('data-lp-frame-source');
                    frame.hidden = filter !== 'all' && source !== filter;
                  });
                });
              });

              document.querySelectorAll('[data-lp-copy-exception]').forEach((button) => {
                button.addEventListener('click', async () => {
                  const label = button.getAttribute('data-lp-copy-label') ?? 'Copy';
                  const copied = button.getAttribute('data-lp-copy-done') ?? 'Copied';

                  try {
                    await copyText(button.getAttribute('data-lp-copy-exception') ?? '');
                    button.textContent = copied;
                    button.classList.add('is-copied');
                    button.classList.remove('is-failed');
                  } catch {
                    button.textContent = 'Failed';
                    button.classList.add('is-failed');
                    button.classList.remove('is-copied');
                  }

                  window.setTimeout(() => {
                    button.textContent = label;
                    button.classList.remove('is-copied', 'is-failed');
                  }, 1800);
                });
              });
            })();
            JS;
    }
}
