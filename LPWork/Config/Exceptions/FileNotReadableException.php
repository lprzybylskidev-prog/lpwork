<?php

declare(strict_types=1);

namespace LPWork\Config\Exceptions;

use RuntimeException;

/**
 * Reports file not readable exception failures.
 */
final class FileNotReadableException extends RuntimeException
{
    /**
     * Creates a new FileNotReadableException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Config file is not readable: {$path}.");
    }
}
