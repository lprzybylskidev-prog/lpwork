<?php

declare(strict_types=1);

namespace LPWork\Session\Exceptions;

use RuntimeException;

/**
 * Reports session save exception failures.
 */
final class SessionSaveException extends RuntimeException
{
    /**
     * Creates a new SessionSaveException instance.
     */
    public function __construct()
    {
        parent::__construct('Could not save the native PHP session.');
    }
}
