<?php

declare(strict_types=1);

namespace LPWork\Logging\Drivers;

use LPWork\Logging\Contracts\LogDriver;
use LPWork\Logging\Exceptions\LogWriteException;
use LPWork\Logging\LogRecord;

/**
 * Represents the fallback log driver framework component.
 */
final readonly class FallbackLogDriver implements LogDriver
{
    /**
     * Creates a new FallbackLogDriver instance.
     */
    public function __construct(
        private LogDriver $primary,
        private LogDriver $fallback,
    ) {}

    /**
     * Registers or stores save.
     */
    public function save(LogRecord $record): void
    {
        try {
            $this->primary->save($record);
        } catch (LogWriteException) {
            $this->fallback->save($record);
        }
    }
}
