<?php

declare(strict_types=1);

namespace LPWork\Storage\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid storage config exception failures.
 */
final class InvalidStorageConfigException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidStorageConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct("Storage configuration value is invalid: {$key}.");
    }
}
