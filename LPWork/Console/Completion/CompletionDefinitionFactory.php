<?php

declare(strict_types=1);

namespace LPWork\Console\Completion;

use function array_map;

use LPWork\Console\CommandRegistry;
use LPWork\Console\ConsoleArgument;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Contracts\HiddenCommand;

/**
 * Creates completion definition factory instances from framework configuration.
 */
final readonly class CompletionDefinitionFactory
{
    /**
     * Creates a new value for this component.
     */
    public function create(CommandRegistry $registry, string $program = 'lpwork'): CompletionDefinition
    {
        $commands = [];

        foreach ($registry->all() as $command) {
            if ($command instanceof HiddenCommand) {
                continue;
            }

            $commands[] = new CompletionCommand(
                $command->name(),
                $command->description(),
                $this->arguments($command),
                $this->options($command),
            );
        }

        return new CompletionDefinition($program, $commands);
    }

    /**
     * @return list<CompletionArgument>
     */
    private function arguments(Command $command): array
    {
        if (!$command instanceof DescribesInput) {
            return [];
        }

        return array_map(
            static fn(ConsoleArgument $argument): CompletionArgument => new CompletionArgument(
                $argument->name(),
                $argument->description(),
                $argument->isRequired(),
            ),
            $command->arguments(),
        );
    }

    /**
     * @return list<CompletionOption>
     */
    private function options(Command $command): array
    {
        $options = [
            ConsoleOption::flag('help', 'h', 'Display help for the command.'),
        ];

        if ($command instanceof DescribesInput) {
            $options = [...$options, ...$command->options()];
        }

        return array_map(
            static fn(ConsoleOption $option): CompletionOption => new CompletionOption(
                $option->name(),
                $option->shortcut(),
                $option->description(),
            ),
            $options,
        );
    }
}
