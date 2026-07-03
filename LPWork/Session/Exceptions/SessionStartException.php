<?php

declare(strict_types=1);

namespace LPWork\Session\Exceptions;

use RuntimeException;

/**
 * Reports session start exception failures.
 */
final class SessionStartException extends RuntimeException
{
    /**
     * Creates a new SessionStartException instance.
     */
    public function __construct()
    {
        parent::__construct('Could not start the native PHP session.');
    }
}
