<?php

declare(strict_types=1);

namespace LPWork\Validation;

use function array_key_exists;

use LPWork\Validation\Exceptions\InvalidValidationRuleParameterException;

/**
 * Represents the validation rule parameter reader framework component.
 */
final readonly class ValidationRuleParameterReader
{
    /**
     * Creates a new ValidationRuleParameterReader instance.
     */
    public function __construct(
        private ValidationStringValue $strings = new ValidationStringValue(),
    ) {}

    /**
     * @param array<array-key, mixed> $parameters
     */
    public function numeric(array $parameters, string $rule, string $parameter): float
    {
        return $this->numericAt($parameters, 0, $rule, $parameter);
    }

    /**
     * @param array<array-key, mixed> $parameters
     */
    public function numericAt(array $parameters, int $index, string $rule, string $parameter): float
    {
        $value = $parameters[$index] ?? null;

        if ($value === null || $value === '') {
            throw InvalidValidationRuleParameterException::missing($rule, $parameter);
        }

        if (!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))) {
            throw InvalidValidationRuleParameterException::numeric($rule, $parameter);
        }

        return (float) $value;
    }

    /**
     * @param array<array-key, mixed> $parameters
     */
    public function string(array $parameters, string $rule, string $parameter): string
    {
        return $this->stringAt($parameters, 0, $rule, $parameter);
    }

    /**
     * @param array<array-key, mixed> $parameters
     */
    public function stringAt(array $parameters, int $index, string $rule, string $parameter): string
    {
        $value = $parameters[$index] ?? null;

        if ($value === null || $value === '') {
            throw InvalidValidationRuleParameterException::missing($rule, $parameter);
        }

        $string = $this->strings->from($value);

        if ($string === null) {
            throw InvalidValidationRuleParameterException::string($rule, $parameter);
        }

        return $string;
    }

    /**
     * @param array<array-key, mixed> $parameters
     *
     * @return non-empty-list<string>
     */
    public function strings(array $parameters, string $rule, string $parameter): array
    {
        if ($parameters === []) {
            throw InvalidValidationRuleParameterException::missing($rule, $parameter);
        }

        $values = [];

        foreach ($parameters as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $string = $this->strings->from($value);

            if ($string === null) {
                throw InvalidValidationRuleParameterException::string($rule, $parameter);
            }

            $values[] = $string;
        }

        if ($values === []) {
            throw InvalidValidationRuleParameterException::missing($rule, $parameter);
        }

        return $values;
    }

    /**
     * @param array<array-key, mixed> $parameters
     */
    public function has(array $parameters, int $index): bool
    {
        return array_key_exists($index, $parameters) && $parameters[$index] !== null && $parameters[$index] !== '';
    }
}
