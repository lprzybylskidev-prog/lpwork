<?php

declare(strict_types=1);

namespace LPWork\Logging\Enums;

/**
 * Enumerates the supported log level values.
 */
enum LogLevel: string
{
    case Debug = 'debug';
    case Info = 'info';
    case Notice = 'notice';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';

    /**
     * Performs the priority operation.
     */
    public function priority(): int
    {
        return match ($this) {
            self::Debug => 100,
            self::Info => 200,
            self::Notice => 250,
            self::Warning => 300,
            self::Error => 400,
            self::Critical => 500,
        };
    }

    /**
     * Reports whether allows.
     */
    public function allows(self $minimum): bool
    {
        return $this->priority() >= $minimum->priority();
    }
}
