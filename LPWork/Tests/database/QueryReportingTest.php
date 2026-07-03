<?php

declare(strict_types=1);

use LPWork\Database\DatabaseDebugCollector;
use LPWork\Database\DatabaseDebugContextProvider;
use LPWork\Database\LoggingQueryReporter;
use LPWork\Database\QueryExecution;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\LogChannel;
use Tests\support\testing\Logging\TestLogDriver;

it('logs query bindings only when app debug is enabled', function (): void {
    $driver = new TestLogDriver();
    $reporter = new LoggingQueryReporter(
        logger: new LogChannel('database', $driver),
        level: LogLevel::Debug,
        appDebug: true,
    );

    $reporter->report(new QueryExecution(
        connection: 'sqlite',
        sql: 'select * from users where email = ?',
        bindings: ['ada@example.test'],
        durationMs: 1.25,
        successful: true,
    ));

    $record = $driver->records()[0];

    expect($record->message)->toBe('Database query executed.')
        ->and($record->context['bindings'])->toBe(['ada@example.test'])
        ->and($record->context)->not->toHaveKey('bindings_count');
});

it('hides query binding values from logs when app debug is disabled', function (): void {
    $driver = new TestLogDriver();
    $reporter = new LoggingQueryReporter(
        logger: new LogChannel('database', $driver),
        level: LogLevel::Debug,
        appDebug: false,
    );

    $reporter->report(new QueryExecution(
        connection: 'sqlite',
        sql: 'select * from users where email = ?',
        bindings: ['ada@example.test'],
        durationMs: 1.25,
        successful: true,
    ));

    $record = $driver->records()[0];

    expect($record->context['bindings_count'])->toBe(1)
        ->and($record->context)->not->toHaveKey('bindings');
});

it('logs failed queries as errors', function (): void {
    $driver = new TestLogDriver();
    $reporter = new LoggingQueryReporter(
        logger: new LogChannel('database', $driver),
        level: LogLevel::Debug,
        appDebug: false,
    );

    $reporter->report(new QueryExecution(
        connection: 'sqlite',
        sql: 'select * from missing',
        bindings: [],
        durationMs: 1.25,
        successful: false,
        exception: new RuntimeException('No table'),
    ));

    $record = $driver->records()[0];

    expect($record->level)->toBe(LogLevel::Error)
        ->and($record->message)->toBe('Database query failed.')
        ->and($record->context['exception'])->toBe(RuntimeException::class);
});

it('exposes recent query diagnostics to http debug context with debug bindings', function (): void {
    $collector = new DatabaseDebugCollector();
    $collector->report(new QueryExecution(
        connection: 'sqlite',
        sql: 'select * from users where id = ?',
        bindings: [15],
        durationMs: 0.5,
        successful: true,
    ));

    $provider = new DatabaseDebugContextProvider($collector, appDebug: true);

    expect($provider->context(new HttpDebugContext()))->toBe([
        'Database' => [
            'Queries' => [
                [
                    'Connection' => 'sqlite',
                    'SQL' => 'select * from users where id = ?',
                    'Duration ms' => 0.5,
                    'Successful' => true,
                    'Bindings' => [15],
                ],
            ],
        ],
    ]);
});

it('hides recent query binding values from http debug context outside app debug', function (): void {
    $collector = new DatabaseDebugCollector();
    $collector->report(new QueryExecution(
        connection: 'sqlite',
        sql: 'select * from users where id = ?',
        bindings: [15],
        durationMs: 0.5,
        successful: true,
    ));

    $provider = new DatabaseDebugContextProvider($collector, appDebug: false);

    expect($provider->context(new HttpDebugContext()))->toBe([
        'Database' => [
            'Queries' => [
                [
                    'Connection' => 'sqlite',
                    'SQL' => 'select * from users where id = ?',
                    'Duration ms' => 0.5,
                    'Successful' => true,
                    'Bindings count' => 1,
                ],
            ],
        ],
    ]);
});
