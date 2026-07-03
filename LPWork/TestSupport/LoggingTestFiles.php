<?php

declare(strict_types=1);

namespace Tests\support;

use DateTimeImmutable;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\LogRecord;
use RuntimeException;

final class LoggingTestFiles
{
    public static function createDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/lpwork_logging_driver_' . uniqid('', true);

        if (!mkdir($directory)) {
            throw new RuntimeException('Could not create temporary logging directory.');
        }

        return $directory;
    }

    public static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            throw new RuntimeException(sprintf('Could not read directory: %s', $directory));
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                self::removeDirectory($path);

                continue;
            }

            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($directory);
    }

    public static function record(DateTimeImmutable $datetime): LogRecord
    {
        return new LogRecord(
            channel: 'app',
            level: LogLevel::Info,
            message: 'Stored',
            context: [],
            datetime: $datetime,
        );
    }
}
