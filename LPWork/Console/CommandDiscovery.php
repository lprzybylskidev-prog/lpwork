<?php

declare(strict_types=1);

namespace LPWork\Console;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Exceptions\InvalidCommandException;
use LPWork\Container\Container;

/**
 * Represents the command discovery framework component.
 */
final readonly class CommandDiscovery
{
    /**
     * Creates a new CommandDiscovery instance.
     */
    public function __construct(
        private Container $container,
    ) {}

    /**
     * @param list<string> $commands
     *
     * @return list<Command>
     */
    public function discover(array $commands): array
    {
        $discovered = [];

        foreach ($commands as $command) {
            $instance = $this->container->make($command);

            if (!$instance instanceof Command) {
                throw InvalidCommandException::classDoesNotImplementCommand($command);
            }

            $discovered[] = $instance;
        }

        return $discovered;
    }
}
