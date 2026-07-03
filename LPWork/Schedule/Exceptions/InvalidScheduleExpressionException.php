<?php

declare(strict_types=1);

namespace LPWork\Schedule\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid schedule expression exception failures.
 */
final class InvalidScheduleExpressionException extends InvalidArgumentException
{
    /**
     * Creates a new InvalidScheduleExpressionException instance.
     */
    public function __construct(string $expression)
    {
        parent::__construct(sprintf('Invalid schedule expression [%s]. Expected five cron fields.', $expression));
    }
}
