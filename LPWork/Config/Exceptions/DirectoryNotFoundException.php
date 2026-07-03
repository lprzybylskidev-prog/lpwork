<?php

declare(strict_types=1);

namespace LPWork\Config\Exceptions;

use RuntimeException;

/**
 * Reports directory not found exception failures.
 */
final class DirectoryNotFoundException extends RuntimeException
{
    /**
     * Creates a new DirectoryNotFoundException instance.
     */
    public function __construct(string $dir)
    {
        parent::__construct("Config directory not found: {$dir}.");
    }
}
