<?php

declare(strict_types=1);

namespace LPWork\Mail\Exceptions;

use InvalidArgumentException;

/**
 * Reports missing mail config exception failures.
 */
final class MissingMailConfigException extends InvalidArgumentException
{
    /**
     * Creates a new MissingMailConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Missing mail configuration value for [%s].', $key));
    }
}
