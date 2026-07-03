<?php

declare(strict_types=1);

namespace LPWork\Emitters\Exceptions;

use RuntimeException;

/**
 * Reports response output write exception failures.
 */
final class ResponseOutputWriteException extends RuntimeException
{
    /**
     * Creates a new ResponseOutputWriteException instance.
     */
    public function __construct()
    {
        parent::__construct('Could not write response output.');
    }
}
