<?php

declare(strict_types=1);

namespace LPWork\Validation\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid validation rule parameter exception failures.
 */
final class InvalidValidationRuleParameterException extends InvalidArgumentException
{
    /**
     * Reports whether missing.
     */
    public static function missing(string $rule, string $parameter): self
    {
        return new self(sprintf(
            'Validation rule [%s] requires parameter [%s].',
            $rule,
            $parameter,
        ));
    }

    /**
     * Performs the numeric operation.
     */
    public static function numeric(string $rule, string $parameter): self
    {
        return new self(sprintf(
            'Validation rule [%s] parameter [%s] must be numeric.',
            $rule,
            $parameter,
        ));
    }

    /**
     * Performs the string operation.
     */
    public static function string(string $rule, string $parameter): self
    {
        return new self(sprintf(
            'Validation rule [%s] parameter [%s] must be text.',
            $rule,
            $parameter,
        ));
    }
}
