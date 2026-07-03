<?php

declare(strict_types=1);

namespace LPWork\Notifications\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid notification config exception failures.
 */
final class InvalidNotificationConfigException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidNotificationConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Invalid notification configuration value [%s].', $key));
    }
}
