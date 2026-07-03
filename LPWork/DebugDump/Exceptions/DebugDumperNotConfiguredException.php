<?php

declare(strict_types=1);

namespace LPWork\DebugDump\Exceptions;

use RuntimeException;

/**
 * Reports debug dumper not configured exception failures.
 */
final class DebugDumperNotConfiguredException extends RuntimeException
{
    /**
     * Creates a new DebugDumperNotConfiguredException instance.
     */
    public function __construct()
    {
        parent::__construct('Debug dumper is not configured.');
    }
}
