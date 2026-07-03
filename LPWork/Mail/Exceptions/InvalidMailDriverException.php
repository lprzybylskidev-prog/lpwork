<?php

declare(strict_types=1);

namespace LPWork\Mail\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid mail driver exception failures.
 */
final class InvalidMailDriverException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidMailDriverException instance.
     */
    public function __construct(string $driver)
    {
        parent::__construct(sprintf('Mail driver [%s] is not supported.', $driver));
    }
}
