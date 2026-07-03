<?php

declare(strict_types=1);

namespace LPWork\Validation;

use LPWork\Validation\Contracts\ValidationRule;
use LPWork\Validation\Exceptions\InvalidValidationRuleDeclarationException;

/**
 * Validates input arrays against named rules and custom rule objects.
 */
final readonly class Validator
{
    /**
     * Creates a validator backed by the rule registry used to resolve string declarations.
     */
    public function __construct(
        private ValidationRuleRegistry $rules,
    ) {}

    /**
     * Validates input and returns both validated data and field errors.
     *
     * @param array<string, mixed> $input Input data, including nested arrays addressable with dot notation.
     * @param array<string, mixed> $rules Field-to-rule declarations using pipe strings, rule objects, or arrays of either.
     */
    public function validate(array $input, array $rules): ValidationResult
    {
        $errors = new ValidationErrorBag();
        $validated = [];

        foreach ($rules as $field => $declarations) {
            $value = $this->value($input, $field);
            $fieldRules = $this->normalizeRules($field, $declarations);

            if ($this->shouldSkipMissingField($fieldRules, $input, $field)) {
                continue;
            }

            if ($this->shouldSkipNullableField($fieldRules, $value)) {
                continue;
            }

            foreach ($fieldRules as $declaration) {
                if ($declaration->rule->name() === 'sometimes' || $declaration->rule->name() === 'nullable') {
                    continue;
                }

                $message = $declaration->rule->validate($field, $value, $input, $declaration->parameters);

                if ($message !== null) {
                    $errors->add($field, $message);
                }
            }

            if (!$errors->has($field)) {
                $this->setNestedValue($validated, $field, $value);
            }
        }

        return new ValidationResult($validated, $errors);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function value(array $input, string $field): mixed
    {
        if (array_key_exists($field, $input)) {
            return $input[$field];
        }

        $value = $input;

        foreach (explode('.', $field) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function hasValue(array $input, string $field): bool
    {
        if (array_key_exists($field, $input)) {
            return true;
        }

        $value = $input;

        foreach (explode('.', $field) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        return true;
    }

    /**
     * @param list<ValidationRuleDeclaration> $rules
     * @param array<string, mixed> $input
     */
    private function shouldSkipMissingField(array $rules, array $input, string $field): bool
    {
        if ($this->hasRule($rules, 'sometimes')) {
            return !$this->hasValue($input, $field);
        }

        return false;
    }

    /**
     * @param list<ValidationRuleDeclaration> $rules
     */
    private function shouldSkipNullableField(array $rules, mixed $value): bool
    {
        if (!$this->hasRule($rules, 'nullable')) {
            return false;
        }

        return $value === null || $value === '';
    }

    /**
     * @param list<ValidationRuleDeclaration> $rules
     */
    private function hasRule(array $rules, string $name): bool
    {
        foreach ($rules as $rule) {
            if ($rule->rule->name() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $rules
     *
     * @return list<ValidationRuleDeclaration>
     */
    private function normalizeRules(string $field, mixed $rules): array
    {
        if (is_string($rules)) {
            return $this->rulesFromString($field, $rules);
        }

        if ($rules instanceof ValidationRule) {
            return [new ValidationRuleDeclaration($rules)];
        }

        if (!is_array($rules)) {
            throw InvalidValidationRuleDeclarationException::unsupportedType($field);
        }

        $normalized = [];

        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $normalized = [...$normalized, ...$this->rulesFromString($field, $rule)];

                continue;
            }

            if ($rule instanceof ValidationRule) {
                $normalized[] = new ValidationRuleDeclaration($rule);

                continue;
            }

            throw InvalidValidationRuleDeclarationException::unsupportedType($field);
        }

        return $normalized;
    }

    /**
     * @return list<ValidationRuleDeclaration>
     */
    private function rulesFromString(string $field, string $rules): array
    {
        $normalized = [];

        foreach (explode('|', $rules) as $rule) {
            $rule = trim($rule);

            if ($rule === '') {
                throw InvalidValidationRuleDeclarationException::emptyRuleName($field);
            }

            [$name, $parameters] = $this->parseRule($rule);
            $normalized[] = new ValidationRuleDeclaration($this->rules->get($name), $parameters);
        }

        return $normalized;
    }

    /**
     * @return array{0: string, 1: array<array-key, mixed>}
     */
    private function parseRule(string $rule): array
    {
        [$name, $parameters] = array_pad(explode(':', $rule, 2), 2, '');

        return [
            $name,
            $this->parameters($parameters),
        ];
    }

    /**
     * @return list<string>
     */
    private function parameters(string $parameters): array
    {
        if ($parameters === '') {
            return [];
        }

        $values = [];

        foreach (explode(',', $parameters) as $index => $parameter) {
            $values[$index] = trim($parameter);
        }

        return array_values($values);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function setNestedValue(array &$validated, string $field, mixed $value): void
    {
        $target = &$validated;

        foreach (explode('.', $field) as $segment) {
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }

            $target = &$target[$segment];
        }

        $target = $value;
    }
}
