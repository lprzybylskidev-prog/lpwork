<?php

declare(strict_types=1);

namespace LPWork\Storage\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid storage disk exception failures.
 */
final class InvalidStorageDiskException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidStorageDiskException instance.
     */
    public function __construct(string $disk)
    {
        parent::__construct("Storage disk is not configured: {$disk}.");
    }
}
