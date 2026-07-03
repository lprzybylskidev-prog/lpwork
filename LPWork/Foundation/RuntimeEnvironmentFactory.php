<?php

declare(strict_types=1);

namespace LPWork\Foundation;

use LPWork\Foundation\Exceptions\InvalidRuntimeEnvironmentException;

/**
 * Creates runtime environment factory instances from framework configuration.
 */
final readonly class RuntimeEnvironmentFactory
{
    /**
     * @param array<array-key, mixed> $productionEnvironments
     */
    public function create(string $name, array $productionEnvironments): RuntimeEnvironment
    {
        return new RuntimeEnvironment(
            $name,
            $this->productionEnvironments($productionEnvironments),
        );
    }

    /**
     * @param array<array-key, mixed> $configured
     *
     * @return list<string>
     */
    private function productionEnvironments(array $configured): array
    {
        $environments = [];

        foreach ($configured as $environment) {
            if (!is_string($environment) || $environment === '') {
                throw InvalidRuntimeEnvironmentException::invalidProductionEnvironment();
            }

            $environments[] = $environment;
        }

        return $environments;
    }
}
