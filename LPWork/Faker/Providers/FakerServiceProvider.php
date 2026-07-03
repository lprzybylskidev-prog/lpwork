<?php

declare(strict_types=1);

namespace LPWork\Faker\Providers;

use Faker\Factory;
use Faker\Generator;
use Faker\Provider\DateTime;
use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Foundation\ServiceProvider;

/**
 * Registers faker service provider services with the framework container.
 */
final class FakerServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(Generator::class, static function (): Generator {
            DateTime::setDefaultTimezone(Config::getString('app.timezone'));

            return Factory::create(Config::getString('app.lang'));
        });
    }
}
