<?php

declare(strict_types=1);

namespace LPWork\Logging\Contracts;

use LPWork\Logging\LogRecord;

/**
 * Defines the contract for log driver.
 */
interface LogDriver
{
    /**
     * Registers or stores save.
     */
    public function save(LogRecord $record): void;
}
