<?php

declare(strict_types=1);

namespace LPWork\Filesystem\Exceptions;

use RuntimeException;

/**
 * Reports directory read exception failures.
 */
final class DirectoryReadException extends RuntimeException
{
    /**
     * Creates a new DirectoryReadException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Could not read directory: {$path}.");
    }
}
