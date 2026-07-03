<?php

declare(strict_types=1);

namespace LPWork\Config\Exceptions;

use RuntimeException;

/**
 * Reports invalid key exception failures.
 */
final class InvalidKeyException extends RuntimeException
{
    /**
     * Creates a new InvalidKeyException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct("Config key is invalid: {$key}.");
    }
}
