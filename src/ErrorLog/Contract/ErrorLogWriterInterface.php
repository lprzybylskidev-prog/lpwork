<?php
declare(strict_types=1);

namespace LPwork\ErrorLog\Contract;

use LPwork\ErrorLog\ErrorLogEntry;

/**
 * Persists structured error log entries.
 */
interface ErrorLogWriterInterface
{
    /**
     * Persists the given error log entry.
     *
     * @param ErrorLogEntry $entry
     *
     * @return void
     */
    public function write(ErrorLogEntry $entry): void;
}
