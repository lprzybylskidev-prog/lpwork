<?php

declare(strict_types=1);

namespace LPWork\Http\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid spoofed method exception failures.
 */
final class InvalidSpoofedMethodException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidSpoofedMethodException instance.
     */
    public function __construct(string $method)
    {
        parent::__construct(sprintf('HTTP method cannot be spoofed: %s', $method));
    }
}
