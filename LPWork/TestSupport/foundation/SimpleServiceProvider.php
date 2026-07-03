<?php

declare(strict_types=1);

namespace Tests\support\foundation;

use LPWork\Container\Container;
use LPWork\Foundation\ServiceProvider;
use Tests\support\container\SimpleService;

final class SimpleServiceProvider extends ServiceProvider
{
    public function register(Container $container): void
    {
        $container->instance(SimpleService::class, new SimpleService());
    }
}
