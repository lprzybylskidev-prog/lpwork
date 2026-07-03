<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators;

/**
 * Represents the provider registration framework component.
 */
final readonly class ProviderRegistration
{
    private function __construct(
        private string $providerPath,
        private string $methodName,
        private ProviderRegistrationStyle $style,
        private ?string $groupOption = null,
        private ?string $defaultGroup = null,
    ) {}

    public static function list(string $providerPath, string $methodName): self
    {
        return new self($providerPath, $methodName, ProviderRegistrationStyle::List);
    }

    /**
     * Performs the grouped operation.
     */
    public static function grouped(string $providerPath, string $methodName, string $groupOption, string $defaultGroup): self
    {
        return new self($providerPath, $methodName, ProviderRegistrationStyle::Grouped, $groupOption, $defaultGroup);
    }

    /**
     * Performs the for provider path operation.
     */
    public function forProviderPath(string $providerPath): self
    {
        return new self($providerPath, $this->methodName, $this->style, $this->groupOption, $this->defaultGroup);
    }

    /**
     * Performs the provider path operation.
     */
    public function providerPath(): string
    {
        return $this->providerPath;
    }

    /**
     * Returns method name.
     */
    public function methodName(): string
    {
        return $this->methodName;
    }

    /**
     * Performs the style operation.
     */
    public function style(): ProviderRegistrationStyle
    {
        return $this->style;
    }

    /**
     * Performs the group option operation.
     */
    public function groupOption(): ?string
    {
        return $this->groupOption;
    }

    /**
     * Returns default group.
     */
    public function defaultGroup(): ?string
    {
        return $this->defaultGroup;
    }
}
