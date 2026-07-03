<?php

declare(strict_types=1);

namespace LPWork\Config\Contracts;

/**
 * Defines the contract for config definition provider.
 */
interface ConfigDefinitionProvider
{
    /**
     * @return list<class-string<ConfigDefinition>>
     */
    public function configDefinitions(): array;
}
