<?php

declare(strict_types=1);

namespace LPWork\Emitters\Exceptions;

use RuntimeException;

/**
 * Reports http output open exception failures.
 */
final class HttpOutputOpenException extends RuntimeException
{
    /**
     * Creates a new HttpOutputOpenException instance.
     */
    public function __construct()
    {
        parent::__construct('Could not open php://output for Http emitting.');
    }
}
