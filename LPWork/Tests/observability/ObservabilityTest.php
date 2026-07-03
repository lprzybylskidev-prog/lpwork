<?php

declare(strict_types=1);

use LPWork\Database\DatabaseDebugCollector;
use LPWork\Database\QueryExecution;
use LPWork\Events\EventDebugCollector;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Observability\DiagnosticsCollector;
use LPWork\Observability\MetricCollector;
use LPWork\View\ViewDebugCollector;

it('redacts sensitive log context before storing diagnostics', function (): void {
    $collector = new DiagnosticsCollector();

    $collector->recordLog('app', LogLevel::Info, 'User logged in.', [
        'email' => 'dev@example.test',
        'password' => 'secret',
        'nested' => [
            'api_token' => 'abc',
            'visible' => 'yes',
        ],
    ]);

    expect($collector->logs()[0]['context'])->toBe([
        'email' => 'dev@example.test',
        'password' => '[redacted]',
        'nested' => [
            'api_token' => '[redacted]',
            'visible' => 'yes',
        ],
    ]);
});

it('records metrics from framework diagnostics collectors', function (): void {
    $metrics = new MetricCollector();

    new DatabaseDebugCollector(metrics: $metrics)->report(new QueryExecution(
        connection: 'default',
        sql: 'select 1',
        bindings: [],
        durationMs: 2.5,
        successful: true,
    ));
    $events = new EventDebugCollector(metrics: $metrics);
    $event = $events->start(new stdClass());
    $events->finish($event, 0.7, successful: true, recordedAtMs: 10.0);
    new ViewDebugCollector($metrics)->record(
        name: 'welcome',
        path: '/views/welcome.php',
        layout: null,
        dataKeys: [],
        sharedKeys: [],
        sections: [],
        successful: true,
        durationMs: 1.1,
        recordedAtMs: 11.0,
    );

    expect(array_map(static fn($metric) => $metric->name, $metrics->recent()))->toBe([
        'database.query.duration',
        'events.dispatched',
        'views.rendered',
    ])
        ->and(array_map(static fn($metric) => $metric->unit, $metrics->recent()))->toBe([
            'ms',
            'ms',
            'ms',
        ])
        ->and($metrics->recent()[0]->memoryBytes)->toBeGreaterThan(0)
        ->and($metrics->recent()[0]->recordedAtMs)->toBeGreaterThan(0);
});
