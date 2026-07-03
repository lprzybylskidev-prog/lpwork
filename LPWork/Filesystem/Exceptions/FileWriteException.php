<?php

declare(strict_types=1);

namespace LPWork\Filesystem\Exceptions;

use RuntimeException;

/**
 * Reports file write exception failures.
 */
final class FileWriteException extends RuntimeException
{
    /**
     * Creates a new FileWriteException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Could not write file: {$path}.");
    }
}
