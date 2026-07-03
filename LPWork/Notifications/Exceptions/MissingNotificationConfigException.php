<?php

declare(strict_types=1);

namespace LPWork\Notifications\Exceptions;

use InvalidArgumentException;

/**
 * Reports missing notification config exception failures.
 */
final class MissingNotificationConfigException extends InvalidArgumentException
{
    /**
     * Creates a new MissingNotificationConfigException instance.
     */
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Missing notification configuration value [%s].', $key));
    }
}
