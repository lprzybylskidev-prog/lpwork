<?php

declare(strict_types=1);

namespace LPWork\Validation;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\Exceptions\ValidationRuleNotFoundException;

/**
 * Stores and resolves validation rule registry registrations.
 */
final class ValidationRuleRegistry
{
    /**
     * @var array<string, ValidationRule>
     */
    private array $rules = [];

    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(ValidationRule $rule): void
    {
        $this->rules[$rule->name()] = $rule;
    }

    /**
     * Reports whether has.
     */
    public function has(string $name): bool
    {
        return isset($this->rules[$name]);
    }

    /**
     * Returns the requested value from this component.
     */
    public function get(string $name): ValidationRule
    {
        return $this->rules[$name] ?? throw new ValidationRuleNotFoundException($name);
    }
}
