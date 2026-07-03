<?php

declare(strict_types=1);

namespace LPWork\Validation\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid validation rule declaration exception failures.
 */
final class InvalidValidationRuleDeclarationException extends InvalidArgumentException
{
    /**
     * Performs the unsupported type operation.
     */
    public static function unsupportedType(string $field): self
    {
        return new self(sprintf(
            'Validation rule declaration for field [%s] must contain rule names or ValidationRule instances.',
            $field,
        ));
    }

    /**
     * Performs the empty rule name operation.
     */
    public static function emptyRuleName(string $field): self
    {
        return new self(sprintf(
            'Validation rule declaration for field [%s] contains an empty rule name.',
            $field,
        ));
    }
}
