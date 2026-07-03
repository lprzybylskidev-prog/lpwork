<?php

declare(strict_types=1);

namespace LPWork\Responses\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid redirect status exception failures.
 */
final class InvalidRedirectStatusException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidRedirectStatusException instance.
     */
    public function __construct(int $statusCode)
    {
        parent::__construct(sprintf('HTTP redirect status code is invalid: %d.', $statusCode));
    }
}
