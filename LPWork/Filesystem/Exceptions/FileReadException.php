<?php

declare(strict_types=1);

namespace LPWork\Filesystem\Exceptions;

use RuntimeException;

/**
 * Reports file read exception failures.
 */
final class FileReadException extends RuntimeException
{
    /**
     * Creates a new FileReadException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Could not read file: {$path}.");
    }
}
