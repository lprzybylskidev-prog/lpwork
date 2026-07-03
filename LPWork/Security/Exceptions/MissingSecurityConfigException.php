<?php

declare(strict_types=1);

namespace LPWork\Security\Exceptions;

use RuntimeException;

/**
 * Reports missing security config exception failures.
 */
final class MissingSecurityConfigException extends RuntimeException
{
    /**
     * Creates a new MissingSecurityConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Missing security configuration value: %s.', $key));
    }
}
