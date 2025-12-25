<?php
declare(strict_types=1);

namespace Config;

use LPwork\Console\Contract\CommandProviderInterface;

/**
 * Application-level command provider.
 */
class CommandProvider implements CommandProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getCommands(): array
    {
        /** @var array<int, \Symfony\Component\Console\Command\Command> $commands */
        $commands = [];

        return $commands;
    }
}
