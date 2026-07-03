<?php

declare(strict_types=1);

namespace LPWork\Mail\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid mail address exception failures.
 */
final class InvalidMailAddressException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidMailAddressException instance.
     */
    public function __construct(string $address)
    {
        parent::__construct(sprintf('Mail address [%s] is invalid.', $address));
    }
}
