<?php

declare(strict_types=1);

namespace LPWork\Config;

use LPWork\Config\Enums\EnvironmentRequirementType;

/**
 * Represents the environment validation issue framework component.
 */
final readonly class EnvironmentValidationIssue
{
    /**
     * Creates a new EnvironmentValidationIssue instance.
     */
    public function __construct(
        public string $key,
        public EnvironmentRequirementType $expectedType,
        public string $message,
    ) {}
}
