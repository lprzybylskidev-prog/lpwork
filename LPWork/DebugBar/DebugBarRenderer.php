<?php

declare(strict_types=1);

namespace LPWork\DebugBar;

use LPWork\Foundation\FrameworkMetadata;
use LPWork\Frontend\FrameworkAssets;
use LPWork\Observability\DiagnosticsSnapshot;
use LPWork\Observability\Metric;

/**
 * Renders debug bar renderer output.
 */
final readonly class DebugBarRenderer
{
    /**
     * Creates a new DebugBarRenderer instance.
     */
    public function __construct(
        private FrameworkMetadata $metadata = new FrameworkMetadata(),
        private DebugBarValueRenderer $values = new DebugBarValueRenderer(),
    ) {}

    /**
     * Renders this component into its output representation.
     */
    public function render(DiagnosticsSnapshot $snapshot): string
    {
        return $this->renderPayload($this->payload($snapshot, bin2hex(random_bytes(12)), bin2hex(random_bytes(16))));
    }

    /**
     * @return array{id: string, session: string, label: string, summary: string, topMeta: string, status: string, duration: string, tabs: list<array{id: string, name: string, count: ?int, content: string}>}
     */
    public function payload(DiagnosticsSnapshot $snapshot, string $id, string $session): array
    {
        $tabs = $this->tabs($snapshot);
        $request = is_array($snapshot->groups['Request'] ?? null) ? $snapshot->groups['Request'] : [];

        return [
            'id' => $id,
            'session' => $session,
            'label' => $this->requestLabel($snapshot),
            'summary' => $this->summary($snapshot),
            'topMeta' => $this->topMeta($snapshot),
            'status' => $this->responseStatus($snapshot->metrics),
            'duration' => $this->requestDuration($snapshot->metrics),
            'method' => is_string($request['Method'] ?? null) ? $request['Method'] : 'HTTP',
            'path' => is_string($request['Path'] ?? null) ? $request['Path'] : (is_string($request['URL'] ?? null) ? $request['URL'] : 'request'),
            'tabs' => $tabs,
        ];
    }

    /**
     * @param array{id?: string, session?: string, label?: string, summary?: string, topMeta?: string, tabs?: list<array{id: string, name: string, count: ?int, content: string}>} $payload
     */
    public function renderPayload(array $payload, bool $collapsed = false): string
    {
        $tabs = is_array($payload['tabs'] ?? null) ? $payload['tabs'] : [];
        $first = $tabs[0]['id'] ?? null;

        if ($first === null) {
            return '';
        }

        $buttons = '';
        $panels = '';

        foreach ($tabs as $tab) {
            $active = $tab['id'] === $first ? ' is-active' : '';
            $buttons .= sprintf(
                '<button type="button" class="lp-debug-tab%s%s" data-lp-debug-tab="%s"><span>%s</span>%s</button>',
                $active,
                $tab['count'] === null ? ' has-no-count' : '',
                $this->escape($tab['id']),
                $this->escape($tab['name']),
                $tab['count'] === null ? '' : '<b>' . $tab['count'] . '</b>',
            );
            $panels .= sprintf(
                '<section class="lp-debug-panel%s" data-lp-debug-panel="%s">%s</section>',
                $active,
                $this->escape($tab['id']),
                $tab['content'],
            );
        }

        $requestPicker = sprintf(
            '<select class="lp-debug-request-picker" data-lp-debug-request-picker aria-label="Debugged request" hidden><option value="%s">%s</option></select>',
            $this->escape(is_string($payload['id'] ?? null) ? $payload['id'] : ''),
            $this->escape(is_string($payload['label'] ?? null) ? $payload['label'] : 'Current request'),
        );

        return sprintf(
            '<div id="lp-debug-bar" class="lp-debug-bar%s" data-lp-debug-bar data-lp-debug-collapsed="%s" data-lp-debug-session="%s" data-lp-debug-current="%s">%s<div class="lp-debug-console" data-lp-debug-shell><div class="lp-debug-resizer" data-lp-debug-resizer title="Resize debug bar"></div><header class="lp-debug-command">%s<div class="lp-debug-summary" data-lp-debug-summary>%s</div>%s<div class="lp-debug-top-meta" data-lp-debug-top-meta>%s</div><button type="button" class="lp-debug-toggle" data-lp-debug-toggle aria-label="Toggle debug bar">Collapse</button></header><div class="lp-debug-workbench"><nav class="lp-debug-tabs" data-lp-debug-tabs aria-label="Debug diagnostics">%s</nav><main class="lp-debug-panels" data-lp-debug-panels>%s</main></div></div><button type="button" class="lp-debug-dock" data-lp-debug-dock><span>LP</span> Debug</button>%s</div>',
            $collapsed ? ' is-collapsed' : '',
            $collapsed ? '1' : '0',
            $this->escape(is_string($payload['session'] ?? null) ? $payload['session'] : ''),
            $this->escape(is_string($payload['id'] ?? null) ? $payload['id'] : ''),
            $this->style(),
            FrameworkAssets::brand('Debugbar', 'lp-ui-framework-brand lp-debug-brand'),
            $this->escape(is_string($payload['summary'] ?? null) ? $payload['summary'] : ''),
            $requestPicker,
            is_string($payload['topMeta'] ?? null) ? $payload['topMeta'] : '',
            $buttons,
            $panels,
            $this->script(),
        );
    }

    /**
     * @return list<array{id: string, name: string, count: ?int, content: string}>
     */
    private function tabs(DiagnosticsSnapshot $snapshot): array
    {
        $tabs = [
            $this->tab('Metrics', count($snapshot->metrics), $this->metrics($snapshot->metrics)),
            $this->tab('WebSocket', 0, $this->emptyState('No websocket activity recorded.')),
        ];

        foreach (['Request', 'Response', 'Request data', 'Route', 'Middleware', 'Session', 'Security', 'Throttle', 'Events', 'Database', 'Views', 'Logs', 'Cache', 'Queue', 'Scheduler'] as $name) {
            if ($name === 'Logs') {
                $tabs[] = $this->tab($name, count($snapshot->logs), $this->logs($snapshot->logs));

                continue;
            }

            $content = $this->tabContent($name, $snapshot->groups[$name] ?? null);

            $tabs[] = $this->tab($name, $this->tabCount($name, $snapshot->groups[$name] ?? null), $content);
        }

        foreach ($snapshot->groups as $name => $data) {
            if ($this->hasTab($tabs, $name)) {
                continue;
            }

            $tabs[] = $this->tab($name, $this->tabCount($name, $data), $this->value($data));
        }

        return $tabs;
    }

    /**
     * @return array{id: string, name: string, count: ?int, content: string}
     */
    private function tab(string $name, ?int $count, string $content): array
    {
        return [
            'id' => $this->id($name),
            'name' => $name,
            'count' => $count,
            'content' => $content,
        ];
    }

    /**
     * @param list<array{id: string, name: string, count: ?int, content: string}> $tabs
     */
    private function hasTab(array $tabs, string $name): bool
    {
        foreach ($tabs as $tab) {
            if ($tab['name'] === $name) {
                return true;
            }
        }

        return false;
    }

    private function summary(DiagnosticsSnapshot $snapshot): string
    {
        $queries = $this->countNestedGroupItems($snapshot->groups, 'Database', 'Queries');
        $events = $this->countGroupItems($snapshot->groups['Events'] ?? null);
        $views = $this->countNestedGroupItems($snapshot->groups, 'Views', 'Renders');
        $cache = $this->countNestedGroupItems($snapshot->groups, 'Cache', 'Operations');
        $queue = $this->countNestedGroupItems($snapshot->groups, 'Queue', 'Jobs');

        return sprintf('%d queries / %d events / %d views / %d cache / %d queue', $queries, $events, $views, $cache, $queue);
    }

    private function requestLabel(DiagnosticsSnapshot $snapshot): string
    {
        $request = is_array($snapshot->groups['Request'] ?? null) ? $snapshot->groups['Request'] : [];
        $method = is_string($request['Method'] ?? null) ? $request['Method'] : 'HTTP';
        $path = is_string($request['Path'] ?? null) ? $request['Path'] : (is_string($request['URL'] ?? null) ? $request['URL'] : 'request');

        return sprintf('%s %s · %s · %s', $method, $path, $this->responseStatus($snapshot->metrics), $this->requestDuration($snapshot->metrics));
    }

    private function topMeta(DiagnosticsSnapshot $snapshot): string
    {
        $duration = $this->requestDuration($snapshot->metrics);
        $memory = $this->formatBytes(memory_get_peak_usage(true));

        return sprintf(
            '<span title="Framework version">LPWork %s</span><span title="Total time">%s</span><span title="Peak memory">%s</span><span title="PHP version">PHP %s</span>',
            $this->escape($this->metadata->version()),
            $this->escape($duration),
            $this->escape($memory),
            $this->escape(PHP_VERSION),
        );
    }

    /**
     * @param list<Metric> $metrics
     */
    private function requestDuration(array $metrics): string
    {
        foreach ($metrics as $metric) {
            if ($metric->name === 'http.request.duration') {
                return $this->number((float) $metric->value) . $metric->unit;
            }
        }

        $last = 0.0;

        foreach ($metrics as $metric) {
            $last = max($last, $metric->recordedAtMs);
        }

        return $this->number($last) . 'ms';
    }

    /**
     * @param list<Metric> $metrics
     */
    private function responseStatus(array $metrics): string
    {
        foreach ($metrics as $metric) {
            $status = $metric->tags['status'] ?? null;

            if (is_int($status) || is_string($status)) {
                return (string) $status;
            }
        }

        return 'n/a';
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

    private function tabCount(string $name, mixed $data): ?int
    {
        return match ($name) {
            'Metrics' => is_array($data) ? count($data) : null,
            'Logs' => is_array($data) ? count($data) : 0,
            'Events' => $this->countGroupItems($data),
            'Database' => $this->countNamedList($data, 'Queries'),
            'Views' => $this->countNamedList($data, 'Renders'),
            'Cache' => $this->countNamedList($data, 'Operations'),
            'Queue' => $this->countNamedList($data, 'Jobs'),
            'Scheduler' => $this->countNamedList($data, 'Tasks'),
            'Security' => $this->countNamedList($data, 'Denials'),
            'Throttle' => $this->countNamedList($data, 'Denials'),
            default => null,
        };
    }

    private function tabContent(string $name, mixed $data): string
    {
        return match ($name) {
            'Events' => $this->recordListTab($data, 'No events recorded.'),
            'Database' => $this->namedRecordListTab($data, 'Queries', 'No database queries recorded.'),
            'Views' => $this->namedRecordListTab($data, 'Renders', 'No views rendered.'),
            'Cache' => $this->namedRecordListTab($data, 'Operations', 'No cache operations recorded.'),
            'Queue' => $this->namedRecordListTab($data, 'Jobs', 'No queue jobs recorded.'),
            'Scheduler' => $this->namedRecordListTab($data, 'Tasks', 'No scheduler tasks recorded.'),
            'Security' => $this->namedRecordListTab($data, 'Denials', 'No security denials recorded.'),
            'Throttle' => $this->namedRecordListTab($data, 'Denials', 'No throttled requests recorded.'),
            default => is_array($data) ? $this->value($data) : $this->emptyState('No diagnostics recorded.'),
        };
    }

    private function recordListTab(mixed $data, string $emptyMessage): string
    {
        if (!is_array($data) || $data === []) {
            return $this->emptyState($emptyMessage);
        }

        return $this->value($data);
    }

    private function namedRecordListTab(mixed $data, string $key, string $emptyMessage): string
    {
        if (!is_array($data)) {
            return $this->emptyState($emptyMessage);
        }

        $items = $data[$key] ?? null;

        if (!is_array($items) || $items === []) {
            return $this->emptyState($emptyMessage);
        }

        return $this->value($items);
    }

    private function countNamedList(mixed $data, string $key): int
    {
        if (!is_array($data)) {
            return 0;
        }

        return $this->countGroupItems($data[$key] ?? null);
    }

    /**
     * @param array<string, mixed> $groups
     */
    private function countNestedGroupItems(array $groups, string $group, string $key): int
    {
        $value = $groups[$group] ?? null;

        if (!is_array($value)) {
            return 0;
        }

        return $this->countGroupItems($value[$key] ?? null);
    }

    private function countGroupItems(mixed $value): int
    {
        return is_array($value) ? count($value) : 0;
    }

    /**
     * @param list<array{channel: string, level: string, message: string, context: array<string, mixed>}> $logs
     */
    private function logs(array $logs): string
    {
        if ($logs === []) {
            return $this->emptyState('No logs recorded.');
        }

        $items = '';

        foreach ($logs as $log) {
            $items .= $this->record([
                'Level' => $log['level'],
                'Message' => $log['message'],
                'Channel' => $log['channel'],
                'Context' => $log['context'],
            ], [$log['level'], $log['message'], $log['channel']]);
        }

        return $this->recordList($items);
    }

    /**
     * @param list<Metric> $metrics
     */
    private function metrics(array $metrics): string
    {
        if ($metrics === []) {
            return $this->emptyState('No metrics recorded.');
        }

        $metrics = $this->sortedTimelineMetrics($metrics);
        [$start, $end] = $this->timelineWindow($metrics);
        $total = max(1.0, $end - $start);
        $records = sprintf(
            '<div class="lp-debug-data-card lp-debug-timeline" style="--lp-debug-total:%s"><header class="lp-debug-data-head lp-debug-timeline-head"><span>Metric</span><span>Timeline</span><span>Start</span><span>Duration</span><span>Memory</span></header>',
            $this->escape($this->number($total)),
        );

        foreach ($metrics as $metric) {
            $duration = $this->metricDurationMs($metric);
            $started = $this->metricStartMs($metric) - $start;
            $left = min(99.2, max(0.0, ($started / $total) * 100));
            $width = max(2.4, ($duration / $total) * 100);
            $width = min(100.0 - $left, $width);
            $isEvent = $duration <= 0.0;
            $isShortBar = !$isEvent && $width < 12.0;
            $records .= sprintf(
                '<article class="lp-debug-timeline-row"><span class="lp-debug-timeline-name">%s</span><span class="lp-debug-timeline-track" aria-label="%s">%s</span><span class="lp-debug-timeline-cell">+%sms</span><span class="lp-debug-timeline-cell">%s</span><span class="lp-debug-timeline-cell">%s</span></article>',
                $this->escape($metric->name),
                $this->escape($metric->name . ' ' . $this->metricLabel($metric)),
                $isEvent
                    ? sprintf(
                        '<span class="lp-debug-timeline-event" style="left:%s%%"><span>%s</span></span>',
                        $this->escape($this->number($left)),
                        $this->escape($this->metricLabel($metric)),
                    )
                    : sprintf(
                        '<span class="lp-debug-timeline-bar%s" style="left:%s%%;width:%s%%"%s tabindex="0"><span>%s</span></span>',
                        $isShortBar ? ' is-short' : '',
                        $this->escape($this->number($left)),
                        $this->escape($this->number($width)),
                        $isShortBar ? ' data-lp-debug-tooltip="' . $this->escape($this->metricLabel($metric)) . '"' : '',
                        $this->escape($this->metricLabel($metric)),
                    ),
                $this->escape($this->number($started)),
                $this->escape($isEvent ? 'event: ' . $this->metricLabel($metric) : $this->number($duration) . ' ms'),
                $this->escape($this->formatBytes($metric->memoryBytes)),
            );
        }

        return $records . '</div>';
    }

    /**
     * @param list<Metric> $metrics
     * @return list<Metric>
     */
    private function sortedTimelineMetrics(array $metrics): array
    {
        uasort($metrics, function (Metric $left, Metric $right): int {
            $byStart = $this->metricStartMs($left) <=> $this->metricStartMs($right);

            if ($byStart !== 0) {
                return $byStart;
            }

            $byEnd = $this->metricEndMs($left) <=> $this->metricEndMs($right);

            if ($byEnd !== 0) {
                return $byEnd;
            }

            return $left->name <=> $right->name;
        });

        return array_values($metrics);
    }

    /**
     * @param list<Metric> $metrics
     * @return array{0: float, 1: float}
     */
    private function timelineWindow(array $metrics): array
    {
        $start = null;
        $end = null;

        foreach ($metrics as $metric) {
            $metricStart = $this->metricStartMs($metric);
            $metricEnd = $this->metricEndMs($metric);
            $start = $start === null ? $metricStart : min($start, $metricStart);
            $end = $end === null ? $metricEnd : max($end, $metricEnd);
        }

        return [$start ?? 0.0, max(($start ?? 0.0) + 1.0, $end ?? 1.0)];
    }

    private function metricDurationMs(Metric $metric): float
    {
        if ($metric->unit === 'ms') {
            return max(0.0, (float) $metric->value);
        }

        return 0.0;
    }

    private function metricStartMs(Metric $metric): float
    {
        return max(0.0, $metric->recordedAtMs);
    }

    private function metricEndMs(Metric $metric): float
    {
        return $this->metricStartMs($metric) + $this->metricDurationMs($metric);
    }

    private function metricLabel(Metric $metric): string
    {
        return $metric->unit === 'ms'
            ? $this->number((float) $metric->value) . ' ms'
            : $this->stringify($metric->value) . ' ' . $metric->unit;
    }

    private function value(mixed $value): string
    {
        return $this->values->value($value);
    }

    private function emptyState(string $message): string
    {
        return $this->values->emptyState($message);
    }

    private function recordList(string $items): string
    {
        return $this->values->recordList($items);
    }

    /**
     * @param array<string|int, mixed> $data
     * @param list<string> $summaryParts
     */
    private function record(array $data, array $summaryParts, ?string $recordId = null): string
    {
        return $this->values->record($data, $summaryParts, $recordId);
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_float($value)) {
            return $this->number($value);
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : get_debug_type($value);
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    private function id(string $name): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $name));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function style(): string
    {
        return '<style>' . FrameworkAssets::stylesheet() . <<<'HTML'
            
            .lp-debug-bar{--lp-blue:var(--lp-ui-blue-strong);--lp-ink:var(--lp-ui-text);--lp-muted:var(--lp-ui-muted);--lp-faint:var(--lp-ui-faint);--lp-rule:#263241;--lp-deep:#080d13;--lp-panel:#0d141d;--lp-panel-2:#111b26;background:#080d13;bottom:0;color:var(--lp-ink);font:13px Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;left:0;position:fixed;right:0;z-index:2147483647}
            .lp-debug-bar .lp-debug-console{background:var(--lp-panel);border:1px solid var(--lp-rule);border-bottom:0;border-radius:8px 8px 0 0;box-shadow:0 -24px 80px rgba(0,0,0,.48);height:min(62vh,560px);margin:0 10px;max-height:86vh;min-height:300px;overflow:hidden;position:relative}
            .lp-debug-bar .lp-debug-resizer{cursor:ns-resize;height:9px;left:0;position:absolute;right:0;top:0;z-index:2}.lp-debug-bar .lp-debug-resizer:after{background:var(--lp-faint);border-radius:999px;content:"";display:block;height:3px;margin:3px auto 0;width:52px}
            .lp-debug-bar .lp-debug-command{align-items:center;background:#0b1118;border-bottom:1px solid var(--lp-rule);display:grid;gap:12px;grid-template-columns:auto minmax(150px,1fr) minmax(220px,320px) auto auto;min-height:58px;padding:12px 14px 10px}.lp-debug-bar .lp-debug-brand{gap:9px}.lp-debug-bar .lp-debug-brand img{display:block;height:28px;width:28px}.lp-debug-bar .lp-debug-summary{color:#d9e5f4;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.lp-debug-bar .lp-debug-request-picker{appearance:none;background:#080d13;border:1px solid var(--lp-rule);border-radius:6px;color:#d9e5f4;font:12px ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;min-width:0;padding:7px 28px 7px 9px;width:100%}.lp-debug-bar .lp-debug-request-picker[hidden]{display:none}.lp-debug-bar .lp-debug-top-meta{align-items:center;color:var(--lp-muted);display:flex;gap:7px;justify-content:flex-end;min-width:0;white-space:nowrap}.lp-debug-bar .lp-debug-top-meta span{background:#080d13;border:1px solid var(--lp-rule);border-radius:6px;flex:0 0 auto;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;padding:6px 8px}
            .lp-debug-bar .lp-debug-toggle,.lp-debug-bar .lp-debug-dock{background:rgba(66,136,206,.15);border:1px solid rgba(101,169,237,.42);border-radius:6px;color:var(--lp-blue);cursor:pointer;font:inherit;font-weight:800;padding:7px 10px}.lp-debug-bar .lp-debug-toggle:hover,.lp-debug-bar .lp-debug-dock:hover{background:rgba(66,136,206,.24);color:#fff}
            .lp-debug-bar .lp-debug-workbench{display:grid;grid-template-columns:230px minmax(0,1fr);height:calc(100% - 58px)}.lp-debug-bar .lp-debug-tabs{background:#080d13;border-right:1px solid var(--lp-rule);display:grid;gap:3px;align-content:start;overflow:auto;padding:10px}.lp-debug-bar .lp-debug-tab{align-items:center;background:transparent;border:1px solid transparent;border-radius:6px;color:#a8b6c8;cursor:pointer;display:grid;font:inherit;gap:8px;grid-template-columns:minmax(0,1fr) auto;min-height:36px;padding:8px 10px;text-align:left}.lp-debug-bar .lp-debug-tab.has-no-count{grid-template-columns:minmax(0,1fr)}.lp-debug-bar .lp-debug-tab:hover{background:#121b26;border-color:#2c3a4c;color:#fff}.lp-debug-bar .lp-debug-tab b{color:var(--lp-blue);font-size:12px;font-weight:900}.lp-debug-bar .lp-debug-tab.is-active{background:#172231;border-color:#3c5773;color:#fff}
            .lp-debug-bar .lp-debug-panels{height:100%;overflow:auto;padding:12px}.lp-debug-bar .lp-debug-panel{display:none}.lp-debug-bar .lp-debug-panel.is-active{display:block}.lp-debug-bar .lp-debug-data-card{background:#080d13;border:1px solid var(--lp-rule);border-radius:7px;display:grid;min-width:0;overflow:hidden}.lp-debug-bar .lp-debug-data-head{align-items:center;background:#111a25;border-bottom:1px solid var(--lp-rule);color:#93a4b8;display:grid;font-size:11px;font-weight:900;gap:12px;grid-template-columns:minmax(190px,280px) minmax(0,1fr);letter-spacing:.06em;min-height:34px;text-transform:uppercase}.lp-debug-bar .lp-debug-data-head span{padding:0 12px}.lp-debug-bar .lp-debug-empty{color:var(--lp-muted);margin:0;padding:14px}
            .lp-debug-bar .lp-debug-records{display:grid;gap:8px;padding:10px}.lp-debug-bar .lp-debug-record{background:#0a1017;border:1px solid var(--lp-rule);border-radius:7px;overflow:hidden}.lp-debug-bar .lp-debug-record>summary{align-items:center;cursor:pointer;display:grid;gap:12px;grid-template-columns:minmax(0,1fr) auto auto;list-style:none;padding:11px 12px}.lp-debug-bar .lp-debug-record>summary:hover{background:#101a25}.lp-debug-bar .lp-debug-record>summary::-webkit-details-marker{display:none}.lp-debug-bar .lp-debug-record>summary span{font-weight:800;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.lp-debug-bar .lp-debug-record>summary small{color:var(--lp-muted);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;white-space:nowrap}.lp-debug-bar .lp-debug-record>summary:after{border-bottom:2px solid var(--lp-blue);border-right:2px solid var(--lp-blue);content:"";height:7px;transform:rotate(-45deg);transition:transform .12s ease;width:7px}.lp-debug-bar .lp-debug-record[open]>summary:after{transform:rotate(45deg)}
            .lp-debug-bar .lp-debug-fields{display:grid;gap:1px;margin:0}.lp-debug-bar .lp-debug-record>.lp-debug-data-card{border:0;border-radius:0;border-top:1px solid var(--lp-rule)}.lp-debug-bar .lp-debug-record>.lp-debug-data-card>.lp-debug-data-head{display:none}.lp-debug-bar .lp-debug-field{background:#0d141d;display:grid;grid-template-columns:minmax(190px,280px) minmax(0,1fr);min-width:0}.lp-debug-bar .lp-debug-field+.lp-debug-field{border-top:1px solid rgba(255,255,255,.055)}.lp-debug-bar .lp-debug-field-name{background:#0a1017;color:#9fb0c4;font-weight:800;line-height:1.45;overflow-wrap:anywhere;padding:10px 12px}.lp-debug-bar .lp-debug-field-value{color:var(--lp-ink);min-width:0;overflow:auto;padding:10px 12px}.lp-debug-bar .lp-debug-field-group{background:#0a1017;border-top:1px solid rgba(255,255,255,.06);display:grid;gap:10px;padding:12px}.lp-debug-bar .lp-debug-field-group:first-child{border-top:0}.lp-debug-bar .lp-debug-field-group>header{align-items:center;display:flex;gap:12px;justify-content:space-between}.lp-debug-bar .lp-debug-field-group>header span{color:#d8e4f3;font-weight:900}.lp-debug-bar .lp-debug-field-group>header small{color:var(--lp-muted);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11px}.lp-debug-bar .lp-debug-inline-records{display:grid;gap:8px}.lp-debug-bar .lp-debug-inline-record{background:#0d141d;border:1px solid rgba(255,255,255,.075);border-radius:6px;overflow:hidden}.lp-debug-bar .lp-debug-inline-record>header{align-items:center;background:#081018;border-bottom:1px solid rgba(255,255,255,.06);display:flex;gap:10px;justify-content:space-between;padding:9px 10px}.lp-debug-bar .lp-debug-inline-record>header span{color:var(--lp-blue);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11px;font-weight:900}.lp-debug-bar .lp-debug-inline-record>header small{color:#b8c6d7;font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.lp-debug-bar .lp-debug-code{background:#081018;border:1px solid rgba(255,255,255,.075);border-radius:5px;color:#e7eef8;display:block;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;line-height:1.5;max-width:100%;overflow:auto;padding:7px 8px;white-space:pre-wrap;word-break:break-word}.lp-debug-bar .lp-debug-code.is-number{color:#8fc7ff}.lp-debug-bar .lp-debug-token-list{display:flex;flex-wrap:wrap;gap:6px}.lp-debug-bar .lp-debug-token{background:#0a1722;border:1px solid #26394d;border-radius:5px;color:#b9d8f7;display:inline-flex;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;line-height:1;padding:5px 7px}.lp-debug-bar .lp-debug-token.is-true{color:#86efac}.lp-debug-bar .lp-debug-token.is-false{color:#fca5a5}.lp-debug-bar .lp-debug-muted{color:var(--lp-muted)}
            .lp-debug-bar .lp-debug-nested{background:#081018;border:1px solid rgba(255,255,255,.075);border-radius:6px;display:grid;overflow:hidden}.lp-debug-bar .lp-debug-nested-row{display:grid;grid-template-columns:minmax(130px,190px) minmax(0,1fr)}.lp-debug-bar .lp-debug-nested-row+.lp-debug-nested-row{border-top:1px solid rgba(255,255,255,.06)}.lp-debug-bar .lp-debug-nested-row>span{background:#0b121b;color:#9fb0c4;font-weight:800;padding:9px 10px}.lp-debug-bar .lp-debug-nested-row>div{min-width:0;padding:9px 10px}.lp-debug-bar .lp-debug-tree{background:#081018;border:1px solid rgba(255,255,255,.075);border-radius:6px;display:grid;max-width:100%;min-width:0;overflow:hidden}.lp-debug-bar .lp-debug-tree-node{min-width:0}.lp-debug-bar .lp-debug-tree-node+.lp-debug-tree-node{border-top:1px solid rgba(255,255,255,.055)}.lp-debug-bar .lp-debug-tree-node summary{cursor:pointer;list-style:none}.lp-debug-bar .lp-debug-tree-node summary::-webkit-details-marker{display:none}.lp-debug-bar .lp-debug-tree-row{align-items:center;background:#0d141d;display:grid;gap:8px;grid-template-columns:18px minmax(120px,190px) minmax(0,1fr);min-width:0;padding:7px 10px}.lp-debug-bar .lp-debug-tree-node.is-branch>summary .lp-debug-tree-row{background:#111a25}.lp-debug-bar .lp-debug-tree-node.is-branch>summary:hover .lp-debug-tree-row{background:#142030}.lp-debug-bar .lp-debug-tree-node.is-leaf>.lp-debug-tree-row{background:#0a1017}.lp-debug-bar .lp-debug-tree-toggle{border-bottom:2px solid var(--lp-blue);border-right:2px solid var(--lp-blue);height:7px;justify-self:center;transform:rotate(-45deg);transition:transform .12s ease;width:7px}.lp-debug-bar .lp-debug-tree-node[open]>summary .lp-debug-tree-toggle{transform:rotate(45deg)}.lp-debug-bar .lp-debug-tree-spacer{height:7px;width:7px}.lp-debug-bar .lp-debug-tree-key{color:#c8d4e2;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;font-weight:900;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.lp-debug-bar .lp-debug-tree-key.is-root{color:#8090a3}.lp-debug-bar .lp-debug-tree-value{align-items:center;display:flex;flex-wrap:wrap;gap:7px;min-width:0}.lp-debug-bar .lp-debug-tree-value>.lp-debug-code{display:inline-block;max-width:100%;padding:3px 6px}.lp-debug-bar .lp-debug-tree-summary{color:#e7eef8;font-weight:900}.lp-debug-bar .lp-debug-tree-details{align-items:center;display:inline-flex;flex-wrap:wrap;gap:7px}.lp-debug-bar .lp-debug-tree-meta{color:var(--lp-muted);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px}.lp-debug-bar .lp-debug-tree-children{border-left:1px solid rgba(101,169,237,.28);display:grid;margin-left:20px}.lp-debug-bar .lp-debug-inline-record .lp-debug-field-name{background:#0b121b}.lp-debug-bar .lp-debug-inline-record .lp-debug-field-value{background:#0d141d}
            .lp-debug-bar .lp-debug-timeline{min-width:920px}.lp-debug-bar .lp-debug-timeline-head,.lp-debug-bar .lp-debug-timeline-row{align-items:center;display:grid;grid-template-columns:minmax(190px,280px) minmax(360px,1fr) 90px 150px 90px}.lp-debug-bar .lp-debug-timeline-head span,.lp-debug-bar .lp-debug-timeline-cell,.lp-debug-bar .lp-debug-timeline-name{padding:0 12px}.lp-debug-bar .lp-debug-timeline-head span:nth-child(n+3){text-align:right}.lp-debug-bar .lp-debug-timeline-head span:nth-child(2){padding-left:0}.lp-debug-bar .lp-debug-timeline-row{border-top:1px solid rgba(255,255,255,.055);min-height:42px}.lp-debug-bar .lp-debug-timeline-row:hover{background:#101923}.lp-debug-bar .lp-debug-timeline-name{font-weight:800;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.lp-debug-bar .lp-debug-timeline-track{background:linear-gradient(90deg,rgba(255,255,255,.09) 0 1px,transparent 1px 25%,rgba(255,255,255,.055) 25% calc(25% + 1px),transparent calc(25% + 1px) 50%,rgba(255,255,255,.055) 50% calc(50% + 1px),transparent calc(50% + 1px) 75%,rgba(255,255,255,.055) 75% calc(75% + 1px),transparent calc(75% + 1px));border-left:1px solid var(--lp-rule);border-right:1px solid var(--lp-rule);height:24px;position:relative}.lp-debug-bar .lp-debug-timeline-bar{align-items:center;background:linear-gradient(90deg,#4288ce,#65a9ed);box-shadow:0 0 0 1px rgba(255,255,255,.16) inset;color:#fff;display:flex;height:100%;min-width:4px;overflow:hidden;position:absolute;top:0}.lp-debug-bar .lp-debug-timeline-bar:focus{outline:2px solid rgba(205,231,255,.8);outline-offset:2px}.lp-debug-bar .lp-debug-timeline-bar.is-short{overflow:visible}.lp-debug-bar .lp-debug-timeline-bar.is-short:after{background:#07111a;border:1px solid #3d6590;border-radius:5px;box-shadow:0 8px 24px rgba(0,0,0,.35);color:#d9efff;content:attr(data-lp-debug-tooltip);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11px;font-weight:900;left:50%;line-height:1;opacity:0;padding:6px 7px;pointer-events:none;position:absolute;top:-8px;transform:translate(-50%,-100%);transition:opacity .12s ease;white-space:nowrap;z-index:3}.lp-debug-bar .lp-debug-timeline-bar.is-short:focus:after,.lp-debug-bar .lp-debug-timeline-bar.is-short:hover:after{opacity:1}.lp-debug-bar .lp-debug-timeline-bar span{display:block;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11px;font-weight:900;max-width:100%;overflow:hidden;padding:0 7px;text-overflow:ellipsis;white-space:nowrap}.lp-debug-bar .lp-debug-timeline-event{bottom:0;position:absolute;top:0;transform:translateX(-1px);width:2px;background:#65a9ed}.lp-debug-bar .lp-debug-timeline-event span{background:#0b1722;border:1px solid #3d6590;border-radius:4px;color:#cde7ff;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:10px;font-weight:900;left:6px;line-height:1;max-width:130px;overflow:hidden;padding:5px 6px;position:absolute;text-overflow:ellipsis;top:50%;transform:translateY(-50%);white-space:nowrap}.lp-debug-bar .lp-debug-timeline-cell{color:var(--lp-muted);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11px;text-align:right}.lp-debug-bar .lp-debug-record.is-highlighted{outline:2px solid rgba(101,169,237,.72);outline-offset:-2px}
            .lp-debug-bar .lp-debug-dock{bottom:12px;display:none;left:12px;position:fixed}.lp-debug-bar .lp-debug-dock span{border:1px solid currentColor;display:inline-block;font-weight:800;line-height:1;margin-right:7px;padding:3px}.lp-debug-bar.is-collapsed .lp-debug-console{display:none}.lp-debug-bar.is-collapsed .lp-debug-dock{display:block}@media(max-width:1120px){.lp-debug-bar .lp-debug-command{grid-template-columns:auto minmax(0,1fr) minmax(180px,280px) auto}.lp-debug-bar .lp-debug-top-meta{display:none}}@media(max-width:960px){.lp-debug-bar .lp-debug-command{grid-template-columns:auto minmax(0,1fr) auto}.lp-debug-bar .lp-debug-request-picker{grid-column:1 / -1}.lp-debug-bar .lp-debug-workbench{grid-template-columns:1fr}.lp-debug-bar .lp-debug-tabs{border-bottom:1px solid var(--lp-rule);border-right:0;display:flex;overflow:auto}.lp-debug-bar .lp-debug-tab{display:flex;white-space:nowrap}.lp-debug-bar .lp-debug-panels{height:calc(100% - 57px)}}@media(max-width:720px){.lp-debug-bar .lp-debug-console{height:min(70vh,520px);max-height:86vh}.lp-debug-bar .lp-debug-field,.lp-debug-bar .lp-debug-nested-row{grid-template-columns:1fr}.lp-debug-bar .lp-debug-tree-row{grid-template-columns:18px minmax(88px,36%) minmax(0,1fr);padding:7px 8px}.lp-debug-bar .lp-debug-tree-children{margin-left:14px}.lp-debug-bar .lp-debug-field-name,.lp-debug-bar .lp-debug-nested-row>span{padding-bottom:4px}.lp-debug-bar .lp-debug-field-value,.lp-debug-bar .lp-debug-nested-row>div{padding-top:4px}.lp-debug-bar .lp-debug-record>summary{grid-template-columns:minmax(0,1fr) auto;gap:4px}.lp-debug-bar .lp-debug-record>summary small{grid-column:1 / -1;white-space:normal}.lp-debug-bar .lp-debug-timeline{gap:0;min-width:0}.lp-debug-bar .lp-debug-timeline-head{display:none}.lp-debug-bar .lp-debug-timeline-row{align-items:stretch;gap:8px;grid-template-columns:1fr;min-height:0;padding:10px 12px}.lp-debug-bar .lp-debug-timeline-name{padding:0;white-space:normal}.lp-debug-bar .lp-debug-timeline-track{border:1px solid var(--lp-rule);height:26px}.lp-debug-bar .lp-debug-timeline-cell{padding:0;text-align:left}.lp-debug-bar .lp-debug-timeline-cell:nth-child(3)::before{content:"Start ";color:var(--lp-muted);font-weight:900}.lp-debug-bar .lp-debug-timeline-cell:nth-child(4)::before{content:"Event / duration ";color:var(--lp-muted);font-weight:900}.lp-debug-bar .lp-debug-timeline-cell:nth-child(5)::before{content:"Memory ";color:var(--lp-muted);font-weight:900}}
            </style>
            HTML;
    }

    private function script(): string
    {
        return <<<'HTML'
            <script>
            (function(){var bar=document.querySelector("[data-lp-debug-bar]");if(!bar){return;}var shell=bar.querySelector("[data-lp-debug-shell]");var panels=bar.querySelector("[data-lp-debug-panels]");var tabs=bar.querySelector("[data-lp-debug-tabs]");var resizer=bar.querySelector("[data-lp-debug-resizer]");var summary=bar.querySelector("[data-lp-debug-summary]");var topMeta=bar.querySelector("[data-lp-debug-top-meta]");var picker=bar.querySelector("[data-lp-debug-request-picker]");var session=bar.getAttribute("data-lp-debug-session")||localStorage.getItem("lpwork.debug.session")||Math.random().toString(36).slice(2)+Date.now().toString(36);var current=bar.getAttribute("data-lp-debug-current")||"";var requests={};var realtime=[];localStorage.setItem("lpwork.debug.session",session);bar.setAttribute("data-lp-debug-session",session);var collapsed=bar.getAttribute("data-lp-debug-collapsed")==="1"||localStorage.getItem("lpwork.debug.collapsed")==="1";var storedHeight=parseInt(localStorage.getItem("lpwork.debug.height")||"",10);function clampHeight(value){var min=220;var max=Math.max(min,Math.floor(window.innerHeight*.82));return Math.min(max,Math.max(min,value));}function setHeight(value){if(!shell){return;}var height=clampHeight(value);shell.style.height=height+"px";localStorage.setItem("lpwork.debug.height",String(height));}if(Number.isFinite(storedHeight)){setHeight(storedHeight);}function setCollapsed(value){bar.classList.toggle("is-collapsed",value);localStorage.setItem("lpwork.debug.collapsed",value?"1":"0");}function activePanel(){return bar.querySelector(".lp-debug-panel.is-active");}function escapeText(value){return String(value).replace(/[&<>"']/g,function(char){return {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"}[char];});}function snapshotPanelState(){var panel=activePanel();var state={tab:null,scroll:panels?panels.scrollTop:0,open:[]};if(!panel){return state;}state.tab=panel.getAttribute("data-lp-debug-panel");panel.querySelectorAll(".lp-debug-record").forEach(function(record,index){if(record.open){state.open.push(index);}});return state;}function restorePanelState(state,activeId){if(!state||state.tab!==activeId){return;}var panel=activePanel();if(!panel){return;}panel.querySelectorAll(".lp-debug-record").forEach(function(record,index){if(state.open.indexOf(index)!==-1){record.open=true;}});if(panels){panels.scrollTop=state.scroll;}}function keepOpenRecordVisible(record){if(!panels||!record||!record.open){return;}requestAnimationFrame(function(){var panelRect=panels.getBoundingClientRect();var recordRect=record.getBoundingClientRect();var scroll=panels.scrollTop;if(recordRect.top<panelRect.top){panels.scrollTop=scroll-(panelRect.top-recordRect.top)-10;return;}if(recordRect.bottom>panelRect.bottom){panels.scrollTop=scroll+(recordRect.bottom-panelRect.bottom)+10;}});}function bindTabs(){bar.querySelectorAll("[data-lp-debug-tab]").forEach(function(tab){tab.addEventListener("click",function(){activateTab(tab.getAttribute("data-lp-debug-tab"));});});bar.querySelectorAll(".lp-debug-record").forEach(function(record){record.addEventListener("toggle",function(){keepOpenRecordVisible(record);});});}function activateTab(id){bar.querySelectorAll("[data-lp-debug-tab]").forEach(function(item){item.classList.toggle("is-active",item.getAttribute("data-lp-debug-tab")===id);});bar.querySelectorAll("[data-lp-debug-panel]").forEach(function(panel){panel.classList.toggle("is-active",panel.getAttribute("data-lp-debug-panel")===id);});if(panels){panels.scrollTop=0;}}function buttonHtml(tab,active){return '<button type="button" class="lp-debug-tab'+(active?' is-active':'')+(tab.count===null?' has-no-count':'')+'" data-lp-debug-tab="'+escapeText(tab.id)+'"><span>'+escapeText(tab.name)+'</span>'+(tab.count===null?'':'<b>'+escapeText(tab.count)+'</b>')+'</button>';}function panelHtml(tab,active){return '<section class="lp-debug-panel'+(active?' is-active':'')+'" data-lp-debug-panel="'+escapeText(tab.id)+'">'+tab.content+'</section>';}function storeRequest(payload){if(!payload||!payload.id){return false;}requests[payload.id]=payload;renderPicker();return true;}function renderRequest(payload){if(!payload||!Array.isArray(payload.tabs)){return;}var panelState=snapshotPanelState();storeRequest(payload);current=payload.id;bar.setAttribute("data-lp-debug-current",current);if(summary){summary.textContent=payload.summary||"";}if(topMeta){topMeta.innerHTML=payload.topMeta||"";}var active=bar.querySelector("[data-lp-debug-tab].is-active");var activeId=active?active.getAttribute("data-lp-debug-tab"):(payload.tabs[0]&&payload.tabs[0].id);if(!payload.tabs.some(function(tab){return tab.id===activeId;})){activeId=payload.tabs[0]&&payload.tabs[0].id;}if(tabs){tabs.innerHTML=payload.tabs.map(function(tab){return buttonHtml(tab,tab.id===activeId);}).join("");}if(panels){panels.innerHTML=payload.tabs.map(function(tab){return panelHtml(tab,tab.id===activeId);}).join("");}bindTabs();restorePanelState(panelState,activeId);renderPicker();}function renderPicker(){var values=Object.keys(requests);if(!picker){return;}picker.innerHTML=values.map(function(id){var request=requests[id];return '<option value="'+escapeText(id)+'"'+(id===current?' selected':'')+'>'+escapeText(request.label||id)+'</option>';}).join("");picker.hidden=values.length<2;}function loadRequest(id,activate){if(!id){return;}if(requests[id]){if(activate){renderRequest(requests[id]);}else{renderPicker();}return;}fetch("/__lpwork/debugbar/request?session="+encodeURIComponent(session)+"&id="+encodeURIComponent(id),{headers:{"X-LPWork-Debug-Session":session,"X-Requested-With":"XMLHttpRequest"}}).then(function(response){return response.ok?response.json():null;}).then(function(data){if(data&&data.request){if(activate){renderRequest(data.request);}else{storeRequest(data.request);}}}).catch(function(){});}function loadRequests(){fetch("/__lpwork/debugbar/requests?session="+encodeURIComponent(session),{headers:{"X-LPWork-Debug-Session":session,"X-Requested-With":"XMLHttpRequest"}}).then(function(response){return response.ok?response.json():null;}).then(function(data){if(!data||!Array.isArray(data.requests)){return;}data.requests.forEach(function(item){if(item&&item.id&&!requests[item.id]){requests[item.id]={id:item.id,label:item.label||item.id,summary:item.summary||"",topMeta:"",tabs:[]};}});renderPicker();}).catch(function(){});}function recordRealtime(type,data){var time=new Date().toLocaleTimeString();realtime.push({Type:type,Time:time,Details:data||""});if(realtime.length>100){realtime.shift();}var request=requests[current];if(!request){return;}var existing=request.tabs.filter(function(tab){return tab.id!=="websocket";});var rows=realtime.length?'<div class="lp-debug-data-card"><header class="lp-debug-data-head"><span>Record</span><span>Details</span></header><div class="lp-debug-records">'+realtime.map(function(item){return '<details class="lp-debug-record"><summary><span>'+escapeText(item.Type)+'</span><small>'+escapeText(item.Time)+'</small></summary><div class="lp-debug-data-card"><div class="lp-debug-fields"><article class="lp-debug-field"><div class="lp-debug-field-name">Details</div><div class="lp-debug-field-value"><code class="lp-debug-code">'+escapeText(item.Details)+'</code></div></article></div></div></details>';}).join("")+'</div></div>':'<div class="lp-debug-data-card"><header class="lp-debug-data-head"><span>Record</span><span>Details</span></header><p class="lp-debug-empty">No websocket activity recorded.</p></div>';request.tabs=existing.concat([{id:"websocket",name:"WebSocket",count:realtime.length,content:rows}]);renderRequest(request);}function isDebugUrl(url){try{var parsed=new URL(url,window.location.href);return parsed.pathname.indexOf("/__lpwork/debugbar")===0;}catch(error){return false;}}setCollapsed(collapsed);bar.querySelector("[data-lp-debug-toggle]").addEventListener("click",function(){setCollapsed(true);});bar.querySelector("[data-lp-debug-dock]").addEventListener("click",function(){setCollapsed(false);});if(picker){picker.addEventListener("change",function(){loadRequest(picker.value,true);});}bindTabs();if(current){requests[current]={id:current,session:session,label:picker&&picker.options[0]?picker.options[0].textContent:"Current request",summary:summary?summary.textContent:"",topMeta:topMeta?topMeta.innerHTML:"",tabs:Array.prototype.map.call(bar.querySelectorAll("[data-lp-debug-tab]"),function(tab){var id=tab.getAttribute("data-lp-debug-tab");var panel=bar.querySelector('[data-lp-debug-panel="'+id+'"]');var count=tab.querySelector("b");return {id:id,name:tab.querySelector("span")?tab.querySelector("span").textContent:id,count:count?parseInt(count.textContent,10):null,content:panel?panel.innerHTML:""};})};renderPicker();}if(resizer&&shell){resizer.addEventListener("pointerdown",function(event){event.preventDefault();resizer.setPointerCapture(event.pointerId);var startY=event.clientY;var startHeight=shell.getBoundingClientRect().height;function move(moveEvent){setHeight(startHeight+(startY-moveEvent.clientY));}function stop(stopEvent){resizer.releasePointerCapture(stopEvent.pointerId);resizer.removeEventListener("pointermove",move);resizer.removeEventListener("pointerup",stop);resizer.removeEventListener("pointercancel",stop);var panel=activePanel();if(panel&&panels){panels.scrollTop=Math.min(panels.scrollTop,panel.scrollHeight);}}resizer.addEventListener("pointermove",move);resizer.addEventListener("pointerup",stop);resizer.addEventListener("pointercancel",stop);});}window.addEventListener("resize",function(){if(shell){setHeight(shell.getBoundingClientRect().height);}});if(window.fetch){var originalFetch=window.fetch;window.fetch=function(input,init){var url=typeof input==="string"?input:(input&&input.url?input.url:String(input));if(isDebugUrl(url)){return originalFetch.apply(this,arguments);}var options=Object.assign({},init||{});var headers=new Headers(options.headers||(input&&input.headers)||{});headers.set("X-LPWork-Debug-Session",session);headers.set("X-Requested-With",headers.get("X-Requested-With")||"XMLHttpRequest");options.headers=headers;return originalFetch.call(this,input,options).then(function(response){var id=response.headers.get("X-LPWork-Debug-Id");if(id){loadRequest(id,false);}return response;});};}if(window.XMLHttpRequest){var OriginalXHR=window.XMLHttpRequest;window.XMLHttpRequest=function(){var xhr=new OriginalXHR();var url="";var open=xhr.open;var send=xhr.send;xhr.open=function(method,target){url=String(target||"");return open.apply(xhr,arguments);};xhr.send=function(){if(!isDebugUrl(url)){try{xhr.setRequestHeader("X-LPWork-Debug-Session",session);xhr.setRequestHeader("X-Requested-With","XMLHttpRequest");}catch(error){}}xhr.addEventListener("loadend",function(){try{var id=xhr.getResponseHeader("X-LPWork-Debug-Id");if(id){loadRequest(id,false);}}catch(error){}});return send.apply(xhr,arguments);};return xhr;};}if(window.WebSocket){var NativeWebSocket=window.WebSocket;window.WebSocket=function(url,protocols){var socket=protocols===undefined?new NativeWebSocket(url):new NativeWebSocket(url,protocols);recordRealtime("connect",String(url));socket.addEventListener("open",function(){recordRealtime("open",String(url));});socket.addEventListener("message",function(event){recordRealtime("message",typeof event.data==="string"?event.data:"binary message");});socket.addEventListener("error",function(){recordRealtime("error",String(url));});socket.addEventListener("close",function(event){recordRealtime("close","code="+event.code+" reason="+(event.reason||""));});return socket;};window.WebSocket.prototype=NativeWebSocket.prototype;window.WebSocket.CONNECTING=NativeWebSocket.CONNECTING;window.WebSocket.OPEN=NativeWebSocket.OPEN;window.WebSocket.CLOSING=NativeWebSocket.CLOSING;window.WebSocket.CLOSED=NativeWebSocket.CLOSED;}loadRequests();})();
            </script>
            HTML;
    }
}
