<?php
declare(strict_types=1);

namespace LPwork\ErrorLog;

use LPwork\ErrorLog\Contract\ErrorLoggerInterface;
use LPwork\ErrorLog\Contract\ErrorLogWriterInterface;
use LPwork\ErrorLog\Exception\ErrorLogWriteException;

/**
 * Logs throwables using configured writer.
 */
class ErrorLogger implements ErrorLoggerInterface
{
    /**
     * @var ErrorLogConfiguration
     */
    private ErrorLogConfiguration $configuration;

    /**
     * @var ErrorLogWriterInterface
     */
    private ErrorLogWriterInterface $writer;

    /**
     * @param ErrorLogConfiguration   $configuration
     * @param ErrorLogWriterInterface $writer
     */
    public function __construct(
        ErrorLogConfiguration $configuration,
        ErrorLogWriterInterface $writer,
    ) {
        $this->configuration = $configuration;
        $this->writer = $writer;
    }

    /**
     * @inheritDoc
     */
    public function log(\Throwable $throwable, array $context = []): string
    {
        $errorId = $this->generateId();
        $entry = $this->buildEntry($errorId, $throwable, $context);

        try {
            $this->writer->write($entry);
        } catch (\Throwable $exception) {
            throw new ErrorLogWriteException(
                "Failed to write error log entry.",
                0,
                $exception,
            );
        }

        return $errorId;
    }

    /**
     * @param string     $errorId
     * @param \Throwable $throwable
     * @param array<string, mixed> $context
     *
     * @return ErrorLogEntry
     */
    private function buildEntry(
        string $errorId,
        \Throwable $throwable,
        array $context,
    ): ErrorLogEntry {
        $timestamp = new \DateTimeImmutable("now", new \DateTimeZone("UTC"));
        $code = (int) $throwable->getCode();
        $line = (int) $throwable->getLine();
        $trace = $throwable->getTraceAsString();
        $file = $throwable->getFile();
        $message = $throwable->getMessage();
        $exceptionClass = \get_class($throwable);
        $context["level"] = $this->configuration->level();

        return new ErrorLogEntry(
            $errorId,
            $this->configuration->level(),
            $message,
            $code,
            $exceptionClass,
            $file,
            $line,
            $trace,
            $context,
            $timestamp,
        );
    }

    /**
     * @return string
     */
    private function generateId(): string
    {
        return \bin2hex(\random_bytes(16));
    }
}
