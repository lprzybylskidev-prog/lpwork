<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Simple built-in command to verify console wiring.
 */
class HelloWorldCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName("hello:world");
        $this->setDescription("Outputs a friendly greeting.");
    }

    /**
     * @inheritDoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $output->writeln("<info>Hello, world from LPwork CLI!</info>");

        return Command::SUCCESS;
    }
}
