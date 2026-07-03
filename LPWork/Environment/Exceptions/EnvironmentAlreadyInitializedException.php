<?php

declare(strict_types=1);

namespace LPWork\Environment\Exceptions;

use RuntimeException;

/**
 * Reports environment already initialized exception failures.
 */
final class EnvironmentAlreadyInitializedException extends RuntimeException
{
    /**
     * Creates a new EnvironmentAlreadyInitializedException instance.
     */
    public function __construct()
    {
        parent::__construct('Environment has already been initialized.');
    }
}
