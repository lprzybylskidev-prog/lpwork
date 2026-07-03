<?php

declare(strict_types=1);

namespace App\Modules\Welcome;

use App\Modules\Welcome\Assets\AssetsProvider;
use App\Modules\Welcome\Broadcasting\BroadcastingProvider;
use App\Modules\Welcome\Configs\ConfigsProvider;
use App\Modules\Welcome\Routes\RoutesProvider;
use App\Modules\Welcome\Translation\TranslationProvider;
use App\Modules\Welcome\View\ViewProvider;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Foundation\Providers\ProviderServiceProvider;

final class WelcomeServiceProvider extends ProviderServiceProvider
{
    /**
     * @return list<class-string<ServiceProvider>>
     */
    protected function serviceProviders(): array
    {
        return [
            AssetsProvider::class,
            BroadcastingProvider::class,
            ConfigsProvider::class,
            RoutesProvider::class,
            TranslationProvider::class,
            ViewProvider::class,
        ];
    }
}
