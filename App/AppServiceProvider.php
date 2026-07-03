<?php

declare(strict_types=1);

namespace App;

use App\Modules\Welcome\WelcomeServiceProvider;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Foundation\Providers\ProviderServiceProvider;

final class AppServiceProvider extends ProviderServiceProvider
{
    /**
     * @return list<class-string<ServiceProvider>>
     */
    protected function serviceProviders(): array
    {
        return [
            WelcomeServiceProvider::class,
        ];
    }
}
