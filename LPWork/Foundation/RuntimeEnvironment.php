<?php

declare(strict_types=1);

namespace LPWork\Foundation;

use LPWork\Foundation\Exceptions\InvalidRuntimeEnvironmentException;

/**
 * Represents the runtime environment framework component.
 */
final readonly class RuntimeEnvironment
{
    /**
     * @param list<string> $productionEnvironments
     */
    public function __construct(
        private string $name,
        private array $productionEnvironments,
    ) {
        if ($this->name === '') {
            throw InvalidRuntimeEnvironmentException::emptyName();
        }

        foreach ($this->productionEnvironments as $environment) {
            if ($environment === '') {
                throw InvalidRuntimeEnvironmentException::invalidProductionEnvironment();
            }
        }
    }

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Reports whether is production.
     */
    public function isProduction(): bool
    {
        return in_array($this->name, $this->productionEnvironments, true);
    }

    /**
     * @return list<string>
     */
    public function productionEnvironments(): array
    {
        return $this->productionEnvironments;
    }
}
