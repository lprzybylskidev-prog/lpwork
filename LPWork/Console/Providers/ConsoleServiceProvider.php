<?php

declare(strict_types=1);

namespace LPWork\Console\Providers;

use LPWork\Container\Container;
use LPWork\Foundation\ServiceProvider;

/**
 * Registers console service provider services with the framework container.
 */
final class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Registers the console runtime by delegating each responsibility to its focused registrar.
     */
    public function register(Container $container): void
    {
        foreach ($this->registrars() as $registrar) {
            $registrar->register($container);
        }
    }

    /**
     * @return list<ConsoleServiceRegistrar>
     */
    private function registrars(): array
    {
        return [
            new CoreConsoleServicesRegistrar(),
            new ConsoleCacheConfigRegistrar(),
            new ConsoleFrontendTaskRegistrar(),
            new ConsoleGeneratorRegistrar(),
        ];
    }
}
