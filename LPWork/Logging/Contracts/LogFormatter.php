<?php

declare(strict_types=1);

namespace LPWork\Logging\Contracts;

use LPWork\Logging\LogRecord;

/**
 * Defines the contract for log formatter.
 */
interface LogFormatter
{
    /**
     * Builds or returns format.
     */
    public function format(LogRecord $record): string;
}
