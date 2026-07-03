<?php

declare(strict_types=1);

namespace LPWork\Foundation\Exceptions;

use InvalidArgumentException;
use LPWork\Foundation\Contracts\ServiceProvider;

/**
 * Reports invalid service provider exception failures.
 */
final class InvalidServiceProviderException extends InvalidArgumentException
{
    /**
     * Performs the does not implement contract operation.
     */
    public static function doesNotImplementContract(string $provider): self
    {
        return new self(sprintf('Service provider must implement %s: %s', ServiceProvider::class, $provider));
    }
}
