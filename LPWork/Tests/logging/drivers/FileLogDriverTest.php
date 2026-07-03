<?php

declare(strict_types=1);

use LPWork\Logging\Contracts\LogDriver;
use LPWork\Logging\Drivers\FallbackLogDriver;
use LPWork\Logging\Drivers\FileLogDriver;
use LPWork\Logging\Enums\LogRotation;
use LPWork\Logging\Exceptions\LogWriteException;
use LPWork\Logging\Formatters\LineLogFormatter;
use LPWork\Logging\LogRecord;
use Tests\support\LoggingTestFiles;

it('writes log records to a relative file path and creates directories', function (): void {
    $basePath = LoggingTestFiles::createDirectory();

    try {
        $driver = new FileLogDriver(
            path: 'storage/logs/app.log',
            rotation: null,
            formatter: new LineLogFormatter(),
            basePath: $basePath,
        );

        $driver->save(LoggingTestFiles::record(new DateTimeImmutable('2026-06-20 12:00:00')));

        $path = $basePath . '/storage/logs/app.log';

        expect($path)->toBeFile()
            ->and(file_get_contents($path))->toBe("[2026-06-20 12:00:00] app.info: Stored\n");
    } finally {
        LoggingTestFiles::removeDirectory($basePath);
    }
});

it('rotates log files by day', function (): void {
    $basePath = LoggingTestFiles::createDirectory();

    try {
        $driver = new FileLogDriver(
            path: 'storage/logs/app.log',
            rotation: LogRotation::Daily,
            formatter: new LineLogFormatter(),
            basePath: $basePath,
        );

        $driver->save(LoggingTestFiles::record(new DateTimeImmutable('2026-06-20 12:00:00')));

        expect($basePath . '/storage/logs/app-2026-06-20.log')->toBeFile();
    } finally {
        LoggingTestFiles::removeDirectory($basePath);
    }
});

it('rotates log files by month', function (): void {
    $basePath = LoggingTestFiles::createDirectory();

    try {
        $driver = new FileLogDriver(
            path: 'storage/logs/app.log',
            rotation: LogRotation::Monthly,
            formatter: new LineLogFormatter(),
            basePath: $basePath,
        );

        $driver->save(LoggingTestFiles::record(new DateTimeImmutable('2026-06-20 12:00:00')));

        expect($basePath . '/storage/logs/app-2026-06.log')->toBeFile();
    } finally {
        LoggingTestFiles::removeDirectory($basePath);
    }
});

it('rotates log files by year', function (): void {
    $basePath = LoggingTestFiles::createDirectory();

    try {
        $driver = new FileLogDriver(
            path: 'storage/logs/app.log',
            rotation: LogRotation::Yearly,
            formatter: new LineLogFormatter(),
            basePath: $basePath,
        );

        $driver->save(LoggingTestFiles::record(new DateTimeImmutable('2026-06-20 12:00:00')));

        expect($basePath . '/storage/logs/app-2026.log')->toBeFile();
    } finally {
        LoggingTestFiles::removeDirectory($basePath);
    }
});

it('writes to a fallback file when the primary file cannot be written', function (): void {
    $basePath = LoggingTestFiles::createDirectory();

    try {
        $driver = new FallbackLogDriver(
            primary: new class implements LogDriver {
                public function save(LogRecord $record): void
                {
                    throw new LogWriteException('primary.log');
                }
            },
            fallback: new FileLogDriver(
                path: 'storage/logs/fallback.log',
                rotation: null,
                formatter: new LineLogFormatter(),
                basePath: $basePath,
            ),
        );

        $driver->save(LoggingTestFiles::record(new DateTimeImmutable('2026-06-20 12:00:00')));

        expect(file_get_contents($basePath . '/storage/logs/fallback.log'))->toBe("[2026-06-20 12:00:00] app.info: Stored\n");
    } finally {
        LoggingTestFiles::removeDirectory($basePath);
    }
});
