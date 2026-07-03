<?php

declare(strict_types=1);

namespace LPWork\Config\Contracts;

use LPWork\Config\EnvironmentRequirement;

/**
 * Defines the contract for environment requirement provider.
 */
interface EnvironmentRequirementProvider
{
    /**
     * @return list<EnvironmentRequirement>
     */
    public function environmentRequirements(): array;
}
