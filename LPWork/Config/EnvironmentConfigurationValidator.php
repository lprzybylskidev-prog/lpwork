<?php

declare(strict_types=1);

namespace LPWork\Config;

use LPWork\Config\Enums\EnvironmentRequirementType;
use LPWork\Environment\Environment;
use LPWork\Environment\Exceptions\InvalidValueException;
use LPWork\Environment\Exceptions\MissingVariableException;

/**
 * Represents the environment configuration validator framework component.
 */
final readonly class EnvironmentConfigurationValidator
{
    /**
     * Creates a new EnvironmentConfigurationValidator instance.
     */
    public function __construct(
        private EnvironmentRequirementRegistry $requirements,
    ) {}

    /**
     * Reports whether validate.
     */
    public function validate(): EnvironmentValidationReport
    {
        $checked = 0;
        $issues = [];

        foreach ($this->requirements->all() as $requirement) {
            if (!$this->applies($requirement)) {
                continue;
            }

            $checked++;
            $issue = $this->validateRequirement($requirement);

            if ($issue !== null) {
                $issues[] = $issue;
            }
        }

        return new EnvironmentValidationReport($checked, $issues);
    }

    private function applies(EnvironmentRequirement $requirement): bool
    {
        if ($requirement->conditionKey === null || $requirement->conditionValue === null) {
            return true;
        }

        try {
            return Environment::getString($requirement->conditionKey) === $requirement->conditionValue;
        } catch (MissingVariableException|InvalidValueException) {
            return false;
        }
    }

    private function validateRequirement(EnvironmentRequirement $requirement): ?EnvironmentValidationIssue
    {
        try {
            $value = match ($requirement->type) {
                EnvironmentRequirementType::String => Environment::getString($requirement->key),
                EnvironmentRequirementType::Integer => Environment::getInt($requirement->key),
                EnvironmentRequirementType::Float => Environment::getFloat($requirement->key),
                EnvironmentRequirementType::Boolean => Environment::getBool($requirement->key),
            };
        } catch (MissingVariableException) {
            return new EnvironmentValidationIssue(
                $requirement->key,
                $requirement->type,
                'Missing required environment value.',
            );
        } catch (InvalidValueException) {
            return new EnvironmentValidationIssue(
                $requirement->key,
                $requirement->type,
                'Value cannot be parsed as ' . $requirement->type->value . '.',
            );
        }

        if (is_string($value) && !$requirement->allowEmpty && trim($value) === '') {
            return new EnvironmentValidationIssue(
                $requirement->key,
                $requirement->type,
                'Required environment value is empty.',
            );
        }

        return null;
    }
}
