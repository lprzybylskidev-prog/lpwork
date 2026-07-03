<?php

declare(strict_types=1);

use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\Formatters\LineLogFormatter;
use LPWork\Logging\LogRecord;

it('formats a log record as a readable line', function (): void {
    $record = new LogRecord(
        channel: 'app',
        level: LogLevel::Info,
        message: 'User created',
        context: ['user_id' => 10],
        datetime: new DateTimeImmutable('2026-06-20 12:34:56'),
    );

    $line = new LineLogFormatter()->format($record);

    expect($line)->toBe('[2026-06-20 12:34:56] app.info: User created {"user_id":10}');
});

it('omits context when context is empty', function (): void {
    $record = new LogRecord(
        channel: 'app',
        level: LogLevel::Debug,
        message: 'Ping',
        context: [],
        datetime: new DateTimeImmutable('2026-06-20 12:34:56'),
    );

    $line = new LineLogFormatter()->format($record);

    expect($line)->toBe('[2026-06-20 12:34:56] app.debug: Ping');
});
