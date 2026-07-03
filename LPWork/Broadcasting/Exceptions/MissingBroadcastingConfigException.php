<?php

declare(strict_types=1);

namespace LPWork\Broadcasting\Exceptions;

use InvalidArgumentException;

/**
 * Reports missing broadcasting config exception failures.
 */
final class MissingBroadcastingConfigException extends InvalidArgumentException
{
    /**
     * Creates a new MissingBroadcastingConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Missing broadcasting configuration value [%s].', $key));
    }
}
