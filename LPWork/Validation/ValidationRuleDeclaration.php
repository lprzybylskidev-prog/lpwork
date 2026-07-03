<?php

declare(strict_types=1);

namespace LPWork\Validation;

use LPWork\Validation\Contracts\ValidationRule;

/**
 * @internal
 */
final readonly class ValidationRuleDeclaration
{
    /**
     * @param array<array-key, mixed> $parameters
     */
    public function __construct(
        public ValidationRule $rule,
        public array $parameters = [],
    ) {}
}
