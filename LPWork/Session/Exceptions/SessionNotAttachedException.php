<?php

declare(strict_types=1);

namespace LPWork\Session\Exceptions;

use RuntimeException;

/**
 * Reports session not attached exception failures.
 */
final class SessionNotAttachedException extends RuntimeException
{
    /**
     * Creates a new SessionNotAttachedException instance.
     */
    public function __construct()
    {
        parent::__construct('Session is not attached to the current HTTP request.');
    }
}
