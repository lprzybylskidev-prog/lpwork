<?php

declare(strict_types=1);

namespace LPWork\Validation\Exceptions;

use RuntimeException;

/**
 * Reports form request not initialized exception failures.
 */
final class FormRequestNotInitializedException extends RuntimeException
{
    /**
     * Creates a new FormRequestNotInitializedException instance.
     */
    public function __construct()
    {
        parent::__construct('Form request has not been initialized.');
    }
}
