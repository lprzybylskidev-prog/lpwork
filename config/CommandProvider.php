<?php
declare(strict_types=1);

namespace Config;

use LPwork\Console\Contract\CommandProviderInterface;
use Symfony\Component\Console\Command\Command;

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
        /** @var array<int, Command> $commands */
        $commands = [];

        return $commands;
    }
}
