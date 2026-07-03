<?php

declare(strict_types=1);

namespace LPWork\Filesystem\Exceptions;

use RuntimeException;

/**
 * Reports directory create exception failures.
 */
final class DirectoryCreateException extends RuntimeException
{
    /**
     * Creates a new DirectoryCreateException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Could not create directory: {$path}.");
    }
}
