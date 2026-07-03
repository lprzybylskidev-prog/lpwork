<?php

declare(strict_types=1);

namespace LPWork\Schedule\Providers;

use LPWork\Container\Container;
use LPWork\Foundation\ServiceProvider;
use LPWork\Schedule\ScheduleRegistry;

/**
 * Registers schedule provider services with the framework container.
 */
abstract class ScheduleProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $schedule = $container->make(ScheduleRegistry::class);

        if (!$schedule instanceof ScheduleRegistry) {
            return;
        }

        $this->schedule($schedule);
    }

    abstract protected function schedule(ScheduleRegistry $schedule): void;
}
