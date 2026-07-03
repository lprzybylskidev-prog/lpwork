<?php

declare(strict_types=1);

namespace LPWork\Validation\Exceptions;

use RuntimeException;

/**
 * Reports validation rule not found exception failures.
 */
final class ValidationRuleNotFoundException extends RuntimeException
{
    /**
     * Creates a new ValidationRuleNotFoundException instance.
     */
    public function __construct(string $rule)
    {
        parent::__construct(sprintf('Validation rule [%s] is not registered.', $rule));
    }
}
