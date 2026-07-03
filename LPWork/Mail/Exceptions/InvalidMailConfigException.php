<?php

declare(strict_types=1);

namespace LPWork\Mail\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid mail config exception failures.
 */
final class InvalidMailConfigException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidMailConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Invalid mail configuration value for [%s].', $key));
    }
}
