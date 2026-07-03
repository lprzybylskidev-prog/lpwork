<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Console\Completion\CompletionInstaller;
use LPWork\Console\ConsoleArgument;
use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Exceptions\UnsupportedCompletionShellException;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Console\ProcessEnvironment;

/**
 * Handles the completion install command console command.
 */
final readonly class CompletionInstallCommand implements Command, DescribesInput
{
    /**
     * Creates a new CompletionInstallCommand instance.
     */
    public function __construct(
        private CompletionInstaller $installer = new CompletionInstaller(),
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
        private ?string $homePath = null,
        private ?string $shellPath = null,
        private ProcessEnvironment $environment = new ProcessEnvironment(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'completion:install';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Install shell completion for the current terminal.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $shell = $input->argument(0) ?? $this->currentShell();
        $homePath = $this->homePath();

        if ($shell === null || $shell === '') {
            $this->messages->error($output, 'Cannot detect the current shell. Pass one explicitly: bash, zsh, or fish.');

            return 1;
        }

        if ($homePath === null || $homePath === '') {
            $this->messages->error($output, 'Cannot detect the home directory for shell profile installation.');

            return 1;
        }

        try {
            $installation = $this->installer->install($shell, $homePath);
        } catch (UnsupportedCompletionShellException $exception) {
            $this->messages->error($output, $exception->getMessage());

            return 1;
        }

        $this->messages->success($output, sprintf(
            'Installed %s completion in %s.',
            $installation->shell(),
            $installation->file(),
        ));
        $this->messages->info($output, 'New terminal sessions will load completion automatically.');
        $this->messages->info($output, 'Refresh this terminal now with:');
        $this->messages->command($output, $installation->activationCommand());

        return 0;
    }

    /**
     * @return list<ConsoleArgument>
     */
    public function arguments(): array
    {
        return [
            ConsoleArgument::optional('shell', 'Shell to install completion for: bash, zsh, or fish. Defaults to the current shell.'),
        ];
    }

    /**
     * Returns options.
     */
    public function options(): array
    {
        return [];
    }

    private function currentShell(): ?string
    {
        if ($this->shellPath !== null) {
            return $this->shellPath;
        }

        return $this->environment->get('SHELL');
    }

    private function homePath(): ?string
    {
        if ($this->homePath !== null) {
            return $this->homePath;
        }

        return $this->environment->get('HOME');
    }
}
