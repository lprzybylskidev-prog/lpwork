<?php

declare(strict_types=1);

namespace LPWork\Storage\Exceptions;

use RuntimeException;

/**
 * Reports storage file not found exception failures.
 */
final class StorageFileNotFoundException extends RuntimeException
{
    /**
     * Creates a new StorageFileNotFoundException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Storage file does not exist: {$path}.");
    }
}
