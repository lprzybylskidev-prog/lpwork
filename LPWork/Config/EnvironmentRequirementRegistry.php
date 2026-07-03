<?php

declare(strict_types=1);

namespace LPWork\Config;

use LPWork\Config\Contracts\EnvironmentRequirementProvider;

/**
 * Stores and resolves environment requirement registry registrations.
 */
final readonly class EnvironmentRequirementRegistry
{
    /**
     * @param list<EnvironmentRequirement> $requirements
     */
    public function __construct(private array $requirements = []) {}

    /**
     * @param list<object> $definitions
     */
    public static function fromDefinitions(array $definitions): self
    {
        $requirements = [];

        foreach ($definitions as $definition) {
            if (!$definition instanceof EnvironmentRequirementProvider) {
                continue;
            }

            foreach ($definition->environmentRequirements() as $requirement) {
                $requirements[$requirement->identity()] = $requirement;
            }
        }

        return new self(array_values($requirements));
    }

    /**
     * @return list<EnvironmentRequirement>
     */
    public function all(): array
    {
        return $this->requirements;
    }
}
