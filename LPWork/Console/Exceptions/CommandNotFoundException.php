<?php

declare(strict_types=1);

namespace LPWork\Console\Exceptions;

use RuntimeException;

/**
 * Reports command not found exception failures.
 */
final class CommandNotFoundException extends RuntimeException
{
    /**
     * Creates a new CommandNotFoundException instance.
     */
    public function __construct(string $command)
    {
        parent::__construct("Console command not found: {$command}.");
    }
}
