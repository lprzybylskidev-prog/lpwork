<?php

declare(strict_types=1);

namespace LPWork\Filesystem\Exceptions;

use RuntimeException;

/**
 * Reports directory clear exception failures.
 */
final class DirectoryClearException extends RuntimeException
{
    /**
     * Creates a new DirectoryClearException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Could not clear directory: {$path}.");
    }
}
