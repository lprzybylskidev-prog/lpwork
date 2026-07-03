<?php

declare(strict_types=1);

namespace LPWork\Storage\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid storage driver exception failures.
 */
final class InvalidStorageDriverException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidStorageDriverException instance.
     */
    public function __construct(string $driver)
    {
        parent::__construct("Storage driver is not supported: {$driver}.");
    }
}
