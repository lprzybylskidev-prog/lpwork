<?php

declare(strict_types=1);

use LPWork\Logging\Drivers\FileLogDriver;
use LPWork\Logging\Drivers\StreamLogDriver;
use LPWork\Logging\Formatters\LineLogFormatter;
use Tests\support\LoggingTestFiles;
use Tests\support\testing\Logging\LogDriverContract;

it('keeps the file log driver compatible with the shared log driver contract', function (): void {
    $directory = LoggingTestFiles::createDirectory();

    try {
        $path = $directory . '/app.log';
        $driver = new FileLogDriver($path, rotation: null, formatter: new LineLogFormatter(), basePath: $directory);

        new LogDriverContract($driver, static function () use ($path): string {
            $contents = file_get_contents($path);

            return is_string($contents) ? $contents : '';
        })
            ->verifiesRecordsArePersisted();
    } finally {
        LoggingTestFiles::removeDirectory($directory);
    }
});

it('keeps the stream log driver compatible with the shared log driver contract', function (): void {
    $directory = LoggingTestFiles::createDirectory();

    try {
        $path = $directory . '/stream.log';
        $driver = new StreamLogDriver($path, new LineLogFormatter());

        new LogDriverContract($driver, static function () use ($path): string {
            $contents = file_get_contents($path);

            return is_string($contents) ? $contents : '';
        })
            ->verifiesRecordsArePersisted();
    } finally {
        LoggingTestFiles::removeDirectory($directory);
    }
});
