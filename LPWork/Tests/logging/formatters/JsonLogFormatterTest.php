<?php

declare(strict_types=1);

use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\Formatters\JsonLogFormatter;
use LPWork\Logging\LogRecord;

it('formats a log record as json', function (): void {
    $record = new LogRecord(
        channel: 'app',
        level: LogLevel::Error,
        message: 'Failure',
        context: ['code' => 500],
        datetime: new DateTimeImmutable('2026-06-20 12:34:56'),
    );

    $json = new JsonLogFormatter()->format($record);

    expect(json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR))->toBe([
        'datetime' => '2026-06-20 12:34:56',
        'channel' => 'app',
        'level' => 'error',
        'message' => 'Failure',
        'context' => [
            'code' => 500,
        ],
    ]);
});
