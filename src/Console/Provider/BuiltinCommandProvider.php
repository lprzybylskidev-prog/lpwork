<?php
declare(strict_types=1);

namespace LPwork\Console\Provider;

use LPwork\Console\Command\HelloWorldCommand;
use LPwork\Console\Contract\CommandProviderInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Provides framework built-in console commands.
 */
class BuiltinCommandProvider implements CommandProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getCommands(): array
    {
        return [new HelloWorldCommand()];
    }
}
