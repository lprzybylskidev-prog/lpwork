<?php

declare(strict_types=1);

namespace LPWork\Storage\Exceptions;

use InvalidArgumentException;

/**
 * Reports missing storage config exception failures.
 */
final class MissingStorageConfigException extends InvalidArgumentException
{
    /**
     * Creates a new MissingStorageConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct("Storage configuration value is missing: {$key}.");
    }
}
