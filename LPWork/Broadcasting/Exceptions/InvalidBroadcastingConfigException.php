<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid broadcasting config exception failures.
 */
final class InvalidBroadcastingConfigException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidBroadcastingConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Invalid broadcasting configuration value [%s].', $key));
    }
}
