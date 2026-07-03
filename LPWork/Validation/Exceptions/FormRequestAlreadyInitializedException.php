<?php

declare(strict_types=1);

namespace LPWork\Validation\Exceptions;

use RuntimeException;

/**
 * Reports form request already initialized exception failures.
 */
final class FormRequestAlreadyInitializedException extends RuntimeException
{
    /**
     * Creates a new FormRequestAlreadyInitializedException instance.
     */
    public function __construct()
    {
        parent::__construct('Form request has already been initialized.');
    }
}
