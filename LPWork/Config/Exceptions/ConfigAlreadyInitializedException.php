<?php

declare(strict_types=1);

namespace LPWork\Config\Exceptions;

use RuntimeException;

/**
 * Reports config already initialized exception failures.
 */
final class ConfigAlreadyInitializedException extends RuntimeException
{
    /**
     * Creates a new ConfigAlreadyInitializedException instance.
     */
    public function __construct()
    {
        parent::__construct('Config has already been initialized.');
    }
}
