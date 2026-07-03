<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Console\CommandRegistry;
use LPWork\Console\Completion\CompletionScriptGenerator;
use LPWork\Console\ConsoleArgument;
use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Contracts\HiddenCommand;
use LPWork\Console\Exceptions\UnsupportedCompletionShellException;
use LPWork\Console\Input;
use LPWork\Console\Output;

/**
 * Handles the completion generate command console command.
 */
final readonly class CompletionGenerateCommand implements Command, DescribesInput, HiddenCommand
{
    /**
     * Creates a new CompletionGenerateCommand instance.
     */
    public function __construct(
        private CommandRegistry $commands,
        private CompletionScriptGenerator $generator,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'completion:generate';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Generate a shell completion script.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $shell = $input->argument(0);

        if ($shell === null || $shell === '') {
            $this->messages->error($output, 'Missing shell name. Supported shells: bash, zsh, fish.');

            return 1;
        }

        try {
            $output->write($this->generator->generate($shell, $this->commands));
        } catch (UnsupportedCompletionShellException $exception) {
            $this->messages->error($output, $exception->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * @return list<ConsoleArgument>
     */
    public function arguments(): array
    {
        return [
            ConsoleArgument::required('shell', 'Shell to generate completion for: bash, zsh, or fish.'),
        ];
    }

    /**
     * Returns options.
     */
    public function options(): array
    {
        return [];
    }
}
