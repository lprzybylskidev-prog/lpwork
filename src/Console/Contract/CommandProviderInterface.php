<?php
declare(strict_types=1);

namespace LPwork\Console\Contract;

use Symfony\Component\Console\Command\Command;

/**
 * Provides console commands for the CLI runtime.
 */
interface CommandProviderInterface
{
    /**
     * Returns a list of commands to be registered.
     *
     * @return array<int, Command>
     */
    public function getCommands(): array;
}
