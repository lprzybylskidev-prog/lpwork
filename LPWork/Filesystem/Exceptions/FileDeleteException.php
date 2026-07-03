<?php

declare(strict_types=1);

namespace LPWork\Filesystem\Exceptions;

use RuntimeException;

/**
 * Reports file delete exception failures.
 */
final class FileDeleteException extends RuntimeException
{
    /**
     * Creates a new FileDeleteException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Could not delete file: {$path}.");
    }
}
