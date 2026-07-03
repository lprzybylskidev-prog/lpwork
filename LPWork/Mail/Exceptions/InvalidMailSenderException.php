<?php

declare(strict_types=1);

namespace LPWork\Mail\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid mail sender exception failures.
 */
final class InvalidMailSenderException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidMailSenderException instance.
     */
    public function __construct(string $name)
    {
        parent::__construct(sprintf('Mail sender [%s] is not configured.', $name));
    }
}
