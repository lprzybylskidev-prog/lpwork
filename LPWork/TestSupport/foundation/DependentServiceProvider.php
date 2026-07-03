<?php

declare(strict_types=1);

namespace Tests\support\foundation;

use LPWork\Container\Container;
use LPWork\Foundation\ServiceProvider;
use Tests\support\container\DependentService;
use Tests\support\container\SimpleService;

final class DependentServiceProvider extends ServiceProvider
{
    public function register(Container $container): void
    {
        $service = $container->make(SimpleService::class);

        if ($service instanceof SimpleService) {
            $container->instance(DependentService::class, new DependentService($service));
        }
    }
}
