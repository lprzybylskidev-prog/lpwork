<?php

declare(strict_types=1);

namespace LPWork\Console\Exceptions;

use RuntimeException;

/**
 * Reports console input read exception failures.
 */
final class ConsoleInputReadException extends RuntimeException
{
    /**
     * Creates a new ConsoleInputReadException instance.
     */
    public function __construct()
    {
        parent::__construct('Could not read console input.');
    }
}
