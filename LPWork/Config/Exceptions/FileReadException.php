<?php

declare(strict_types=1);

namespace LPWork\Config\Exceptions;

use RuntimeException;

/**
 * Reports file read exception failures.
 */
final class FileReadException extends RuntimeException
{
    /**
     * Creates a new FileReadException instance.
     */
    public function __construct()
    {
        parent::__construct("Failed to read config files.");
    }
}
