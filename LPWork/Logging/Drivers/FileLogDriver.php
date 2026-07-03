<?php

declare(strict_types=1);

namespace LPWork\Logging\Drivers;

use LPWork\Filesystem\Exceptions\FileWriteException;
use LPWork\Filesystem\Filesystem;
use LPWork\Logging\Contracts\LogDriver;
use LPWork\Logging\Contracts\LogFormatter;
use LPWork\Logging\Enums\LogRotation;
use LPWork\Logging\Exceptions\LogWriteException;
use LPWork\Logging\LogRecord;
use LPWork\Storage\StorageDisk;

use function ltrim;
use function pathinfo;

use const PATHINFO_DIRNAME;
use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;

use function rtrim;
use function sprintf;
use function str_starts_with;

/**
 * Represents the file log driver framework component.
 */
final class FileLogDriver implements LogDriver
{
    /**
     * Creates a new FileLogDriver instance.
     */
    public function __construct(
        private readonly string $path,
        private readonly ?LogRotation $rotation,
        private readonly LogFormatter $formatter,
        private readonly string $basePath,
        private readonly ?StorageDisk $disk = null,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Registers or stores save.
     */
    public function save(LogRecord $record): void
    {
        $path = $this->resolvePath($record);
        $this->append($path, $this->formatter->format($record) . "\n");
    }

    private function resolvePath(LogRecord $record): string
    {
        $path = $this->disk === null ? $this->absolutePath($this->path) : $this->path;

        if ($this->rotation === null) {
            return $path;
        }

        $directory = pathinfo($path, PATHINFO_DIRNAME);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $suffix = match ($this->rotation) {
            LogRotation::Daily => $record->datetime->format('Y-m-d'),
            LogRotation::Monthly => $record->datetime->format('Y-m'),
            LogRotation::Yearly => $record->datetime->format('Y'),
        };

        $path = sprintf('%s/%s-%s', $directory, $filename, $suffix);

        if ($extension !== '') {
            $path .= ".{$extension}";
        }

        return $path;
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($this->basePath, '/') . '/' . ltrim($path, '/');
    }

    private function append(string $path, string $content): void
    {
        try {
            if ($this->disk !== null) {
                $this->disk->append($path, $content);

                return;
            }

            $this->filesystem->append($path, $content);
        } catch (FileWriteException) {
            throw new LogWriteException($path);
        }
    }
}
