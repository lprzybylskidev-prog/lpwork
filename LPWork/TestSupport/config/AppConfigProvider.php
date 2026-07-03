<?php

declare(strict_types=1);

namespace Tests\support\config;

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Providers\ConfigsProvider;

final class AppConfigProvider extends ConfigsProvider
{
    /**
     * @return list<class-string<ConfigDefinition>>
     */
    protected function configDefinitions(): array
    {
        return [
            AppConfigDefinition::class,
        ];
    }
}
