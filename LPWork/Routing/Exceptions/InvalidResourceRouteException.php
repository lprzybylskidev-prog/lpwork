<?php

declare(strict_types=1);

namespace LPWork\Routing\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid resource route exception failures.
 */
final class InvalidResourceRouteException extends InvalidArgumentException
{
    /**
     * @param list<string> $allowedActions
     */
    public static function unknownAction(string $action, array $allowedActions): self
    {
        return new self(sprintf(
            'Unknown resource route action [%s]. Allowed actions are: %s.',
            $action,
            implode(', ', $allowedActions),
        ));
    }
}
