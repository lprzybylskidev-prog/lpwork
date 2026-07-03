<?php

declare(strict_types=1);

namespace LPWork\Mail\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid mail transport exception failures.
 */
final class InvalidMailTransportException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidMailTransportException instance.
     */
    public function __construct(string $name)
    {
        parent::__construct(sprintf('Mail transport [%s] is not configured.', $name));
    }
}
