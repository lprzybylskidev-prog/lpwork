<?php

declare(strict_types=1);

namespace LPWork\Validation\Exceptions;

use LPWork\Validation\ValidationErrorBag;
use RuntimeException;

/**
 * Reports validation exception failures.
 */
final class ValidationException extends RuntimeException
{
    /**
     * Creates a new ValidationException instance.
     */
    public function __construct(
        private readonly ValidationErrorBag $errors,
    ) {
        parent::__construct('Validation failed.');
    }

    /**
     * Performs the errors operation.
     */
    public function errors(): ValidationErrorBag
    {
        return $this->errors;
    }
}
