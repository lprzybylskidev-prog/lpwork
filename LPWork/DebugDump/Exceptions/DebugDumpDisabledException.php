<?php

declare(strict_types=1);

namespace LPWork\DebugDump\Exceptions;

use RuntimeException;

/**
 * Reports debug dump disabled exception failures.
 */
final class DebugDumpDisabledException extends RuntimeException
{
    /**
     * Creates a new DebugDumpDisabledException instance.
     */
    public function __construct()
    {
        parent::__construct('Debug dumping is disabled.');
    }
}
