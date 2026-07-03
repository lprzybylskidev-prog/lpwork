<?php

declare(strict_types=1);

namespace LPWork\Config\Providers;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Contracts\ConfigDefinitionProvider;
use LPWork\Container\Container;
use LPWork\Foundation\ServiceProvider;

/**
 * Registers config definitions provider services with the framework container.
 */
abstract class ConfigDefinitionsProvider extends ServiceProvider implements ConfigDefinitionProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        //
    }

    /**
     * @return list<class-string<ConfigDefinition>>
     */
    abstract public function configDefinitions(): array;
}
