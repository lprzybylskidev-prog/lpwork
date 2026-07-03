<?php

declare(strict_types=1);

namespace LPWork\Config\Exceptions;

use RuntimeException;

/**
 * Reports invalid file exception failures.
 */
final class InvalidFileException extends RuntimeException
{
    /**
     * Creates a new InvalidFileException instance.
     */
    public function __construct(string $path)
    {
        parent::__construct("Config file must return an array: {$path}.");
    }
}
