<?php

declare(strict_types=1);

namespace LPWork\Console\Providers;

use LPWork\Console\CommandDiscovery;
use LPWork\Console\CommandRegistry;
use LPWork\Console\Contracts\Command;
use LPWork\Container\Container;
use LPWork\Foundation\ServiceProvider;

/**
 * Registers commands provider services with the framework container.
 */
abstract class CommandsProvider extends ServiceProvider
{
    /**
     * Registers this provider's services, declarations, or hooks with the container.
     */
    public function register(Container $container): void
    {
        $commands = $container->make(CommandRegistry::class);
        $discovery = $container->make(CommandDiscovery::class);

        if (!$commands instanceof CommandRegistry || !$discovery instanceof CommandDiscovery) {
            return;
        }

        foreach ($discovery->discover($this->commands()) as $command) {
            $commands->add($command);
        }
    }

    /**
     * @return list<class-string<Command>>
     */
    abstract protected function commands(): array;
}
