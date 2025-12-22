<?php
declare(strict_types=1);

namespace LPwork\ErrorLog;

use Carbon\CarbonImmutable;
use LPwork\ErrorLog\Contract\ErrorLoggerInterface;
use LPwork\ErrorLog\Contract\ErrorLogWriterInterface;
use LPwork\ErrorLog\Contract\ErrorIdProviderInterface;
use LPwork\ErrorLog\Exception\ErrorLogWriteException;
use Psr\Clock\ClockInterface;

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
     * @var ClockInterface
     */
    private ClockInterface $clock;

    /**
     * @var ErrorIdProviderInterface
     */
    private ErrorIdProviderInterface $errorIdProvider;

    /**
     * @param ErrorLogConfiguration    $configuration
     * @param ErrorLogWriterInterface  $writer
     * @param ErrorIdProviderInterface $errorIdProvider
     * @param ClockInterface           $clock
     */
    public function __construct(
        ErrorLogConfiguration $configuration,
        ErrorLogWriterInterface $writer,
        ErrorIdProviderInterface $errorIdProvider,
        ClockInterface $clock,
    ) {
        $this->configuration = $configuration;
        $this->writer = $writer;
        $this->errorIdProvider = $errorIdProvider;
        $this->clock = $clock;
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
            throw new ErrorLogWriteException('Failed to write error log entry.', 0, $exception);
        }

        $this->errorIdProvider->setCurrentErrorId($errorId);

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
        $timestamp = CarbonImmutable::instance($this->clock->now());
        $contextStatus = $context['error_context']['status'] ?? null;
        $code = \is_int($contextStatus) ? $contextStatus : (int) $throwable->getCode();

        if ($code === 0) {
            $code = 500;
        }
        $line = (int) $throwable->getLine();
        $trace = $throwable->getTraceAsString();
        $file = $throwable->getFile();
        $message = $throwable->getMessage();
        $exceptionClass = \get_class($throwable);
        $context['level'] = $this->configuration->level();

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
