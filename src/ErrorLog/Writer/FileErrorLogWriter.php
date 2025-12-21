<?php
declare(strict_types=1);

namespace LPwork\ErrorLog\Writer;

use LPwork\ErrorLog\Contract\ErrorLogWriterInterface;
use LPwork\ErrorLog\ErrorLogEntry;
use LPwork\ErrorLog\Exception\ErrorLogWriteException;
use LPwork\Filesystem\FilesystemManager;

/**
 * Writes error log entries to filesystem with basic rotation.
 */
class FileErrorLogWriter implements ErrorLogWriterInterface
{
    /**
     * @var string
     */
    private string $mode;

    /**
     * @var string
     */
    private string $directory;

    /**
     * @var FilesystemManager
     */
    private FilesystemManager $filesystemManager;

    /**
     * @param string             $mode
     * @param string             $directory
     * @param FilesystemManager  $filesystemManager
     */
    public function __construct(
        string $mode,
        string $directory,
        FilesystemManager $filesystemManager,
    ) {
        $this->mode = $mode;
        $this->directory = $directory;
        $this->filesystemManager = $filesystemManager;
    }

    /**
     * @inheritDoc
     */
    public function write(ErrorLogEntry $entry): void
    {
        $disk = $this->filesystemManager->disk();
        $path = $this->buildPath($entry);
        $payload = \json_encode($entry->toArray());

        if ($payload === false) {
            throw new ErrorLogWriteException(
                "Failed to encode error log entry.",
            );
        }

        try {
            $directory = \dirname($path);

            if (!$disk->directoryExists($directory)) {
                $disk->createDirectory($directory);
            }

            $content = $payload . PHP_EOL;

            if ($disk->fileExists($path)) {
                $existing = $disk->read($path);
                $content = $existing . $content;
            }

            $disk->write($path, $content);
        } catch (\Throwable $throwable) {
            throw new ErrorLogWriteException(
                \sprintf('Failed to write error log file "%s".', $path),
                0,
                $throwable,
            );
        }
    }

    /**
     * @param ErrorLogEntry $entry
     *
     * @return string
     */
    private function buildPath(ErrorLogEntry $entry): string
    {
        $baseDir = \rtrim($this->directory, "/");

        if ($baseDir === "") {
            $baseDir = "storage/errors";
        }

        $timestamp = $entry->timestamp();
        $filename = "errors.log";

        if ($this->mode === "daily") {
            $filename = \sprintf("errors-%s.log", $timestamp->format("Y-m-d"));
        } elseif ($this->mode === "monthly") {
            $filename = \sprintf("errors-%s.log", $timestamp->format("Y-m"));
        }

        return $baseDir . "/" . $filename;
    }
}
