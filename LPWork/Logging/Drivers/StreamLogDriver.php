<?php

declare(strict_types=1);

namespace LPWork\Logging\Drivers;

use LPWork\Logging\Contracts\LogDriver;
use LPWork\Logging\Contracts\LogFormatter;
use LPWork\Logging\Exceptions\LogWriteException;
use LPWork\Logging\Formatters\LineLogFormatter;
use LPWork\Logging\LogRecord;

/**
 * Represents the stream log driver framework component.
 */
final readonly class StreamLogDriver implements LogDriver
{
    /**
     * Creates a new StreamLogDriver instance.
     */
    public function __construct(
        private string $stream,
        private LogFormatter $formatter = new LineLogFormatter(),
    ) {}

    /**
     * Registers or stores save.
     */
    public function save(LogRecord $record): void
    {
        $handle = @fopen($this->stream, 'ab');

        if ($handle === false) {
            throw new LogWriteException($this->stream);
        }

        try {
            if (@fwrite($handle, $this->formatter->format($record) . "\n") === false) {
                throw new LogWriteException($this->stream);
            }
        } finally {
            @fclose($handle);
        }
    }
}
