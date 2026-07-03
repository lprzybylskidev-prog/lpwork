<?php

declare(strict_types=1);

namespace LPWork\Security\Exceptions;

use RuntimeException;

/**
 * Reports invalid security config exception failures.
 */
final class InvalidSecurityConfigException extends RuntimeException
{
    /**
     * Creates a new InvalidSecurityConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Invalid security configuration value: %s.', $key));
    }
}
