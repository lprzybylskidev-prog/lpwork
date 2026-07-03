<?php

declare(strict_types=1);

namespace LPWork\Time\Providers;

use DateInvalidTimeZoneException;
use DateTimeZone;
use LPWork\Config\Config;
use LPWork\Container\Container;
use LPWork\Foundation\ServiceProvider;
use LPWork\Time\Contracts\Clock;
use LPWork\Time\Exceptions\InvalidTimezoneException;
use LPWork\Time\SystemClock;

/**
 * Registers time service provider services with the framework container.
 */
final class TimeServiceProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $container->singleton(Clock::class, static function (): Clock {
            $timezone = Config::getString('app.timezone');

            try {
                return new SystemClock(new DateTimeZone($timezone));
            } catch (DateInvalidTimeZoneException) {
                throw new InvalidTimezoneException($timezone);
            }
        });
    }
}
