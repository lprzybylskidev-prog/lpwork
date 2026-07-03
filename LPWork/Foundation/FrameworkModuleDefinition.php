<?php

declare(strict_types=1);

namespace LPWork\Foundation;

/**
 * Represents the framework module definition framework component.
 */
final readonly class FrameworkModuleDefinition
{
    /**
     * Creates a new FrameworkModuleDefinition instance.
     */
    public function __construct(private string $key) {}

    /**
     * Returns the stable key used to identify this object.
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * Returns name translation key.
     */
    public function nameTranslationKey(): string
    {
        return 'lpwork::modules.' . $this->key . '.name';
    }

    /**
     * Returns description translation key.
     */
    public function descriptionTranslationKey(): string
    {
        return 'lpwork::modules.' . $this->key . '.description';
    }
}
