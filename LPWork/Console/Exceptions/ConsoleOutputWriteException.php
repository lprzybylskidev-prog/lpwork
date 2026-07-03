<?php

declare(strict_types=1);

namespace LPWork\Console\Exceptions;

use RuntimeException;

/**
 * Reports console output write exception failures.
 */
final class ConsoleOutputWriteException extends RuntimeException
{
    /**
     * Creates a new ConsoleOutputWriteException instance.
     */
    public function __construct()
    {
        parent::__construct('Could not write console output.');
    }
}
