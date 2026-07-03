<?php

declare(strict_types=1);

namespace App\Modules\Welcome\Configs;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Providers\ConfigDefinitionsProvider;

final class ConfigsProvider extends ConfigDefinitionsProvider
{
    /**
     * @return list<class-string<ConfigDefinition>>
     */
    public function configDefinitions(): array
    {
        return [
            WelcomeConfig::class,
        ];
    }
}
