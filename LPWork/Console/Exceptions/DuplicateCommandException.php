<?php

declare(strict_types=1);

namespace LPWork\Console\Exceptions;

use RuntimeException;

/**
 * Reports duplicate command exception failures.
 */
final class DuplicateCommandException extends RuntimeException
{
    /**
     * Creates a new DuplicateCommandException instance.
     */
    public function __construct(string $command)
    {
        parent::__construct("Console command is already registered: {$command}.");
    }
}
