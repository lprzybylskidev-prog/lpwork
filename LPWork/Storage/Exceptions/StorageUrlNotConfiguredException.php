<?php

declare(strict_types=1);

namespace LPWork\Storage\Exceptions;

use RuntimeException;

/**
 * Reports storage url not configured exception failures.
 */
final class StorageUrlNotConfiguredException extends RuntimeException
{
    /**
     * Creates a new StorageUrlNotConfiguredException instance.
     */
    public function __construct(string $disk)
    {
        parent::__construct("Storage disk does not have a public URL configured: {$disk}.");
    }
}
