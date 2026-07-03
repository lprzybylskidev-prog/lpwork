<?php

declare(strict_types=1);

namespace LPWork\Kernels\Http\Exceptions;

use RuntimeException;

/**
 * Reports form request factory not registered exception failures.
 */
final class FormRequestFactoryNotRegisteredException extends RuntimeException
{
    /**
     * Creates a new FormRequestFactoryNotRegisteredException instance.
     */
    public function __construct()
    {
        parent::__construct('Cannot resolve FormRequest action parameter because the FormRequestFactory is not registered.');
    }
}
