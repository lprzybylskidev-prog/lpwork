<?php

declare(strict_types=1);

namespace LPWork\Console;

use function array_key_exists;
use function ksort;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Exceptions\CommandNotFoundException;
use LPWork\Console\Exceptions\DuplicateCommandException;

/**
 * Stores and resolves command registry registrations.
 */
final class CommandRegistry
{
    /**
     * @var array<string, Command>
     */
    private array $commands = [];

    /**
     * Adds an item to this component's registry or backing store.
     */
    public function add(Command $command): void
    {
        if (array_key_exists($command->name(), $this->commands)) {
            throw new DuplicateCommandException($command->name());
        }

        $this->commands[$command->name()] = $command;
    }

    /**
     * Reports whether has.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->commands);
    }

    /**
     * Returns the requested value from this component.
     */
    public function get(string $name): Command
    {
        if (!$this->has($name)) {
            throw new CommandNotFoundException($name);
        }

        return $this->commands[$name];
    }

    /**
     * @return array<string, Command>
     */
    public function all(): array
    {
        $commands = $this->commands;
        ksort($commands);

        return $commands;
    }
}
