<?php

declare(strict_types=1);

namespace LPWork\Filesystem\Exceptions;

use RuntimeException;

/**
 * Reports file not found exception failures.
 */
final class FileNotFoundException extends RuntimeException
{
    /**
     * Creates a new FileNotFoundException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("File does not exist: {$path}.");
    }
}
