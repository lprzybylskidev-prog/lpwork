<?php

declare(strict_types=1);

namespace LPWork\Console;

use LPWork\Console\Enums\ConsoleColor;
use LPWork\Console\Enums\ConsoleStyle;

/**
 * Renders global help renderer output.
 */
final readonly class GlobalHelpRenderer
{
    /**
     * Creates a new GlobalHelpRenderer instance.
     */
    public function __construct(
        private ConsoleTableRenderer $tables = new ConsoleTableRenderer(),
    ) {}

    /**
     * Renders this component into its output representation.
     */
    public function render(Output $output): void
    {
        $this->renderHeader($output);
        $this->renderUsage($output);
        $this->renderGlobalOptions($output);
        $this->renderConventions($output);
        $this->renderWorkflows($output);
        $this->renderNextSteps($output);
    }

    private function renderHeader(Output $output): void
    {
        $output->writelnFormatted('LPWork Console Help', ConsoleColor::LpworkBlue, styles: [ConsoleStyle::Bold]);
        $output->writeln('Run framework, application, tooling, and diagnostic tasks from one entrypoint.');
        $output->writeln();
    }

    private function renderUsage(Output $output): void
    {
        $output->writelnFormatted('Usage:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);
        $output->writeln('  lpwork');
        $output->writeln('  lpwork --help');
        $output->writeln('  lpwork <command> [arguments] [options]');
        $output->writeln('  lpwork <command> --help');
        $output->writeln();
    }

    private function renderGlobalOptions(Output $output): void
    {
        $output->writelnFormatted('Global Options:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);
        $this->tables->render(ConsoleTable::make(
            ['Option', 'Description'],
            [
                ['-h, --help', 'Display this guide, or command-specific help when used after a command.'],
                ['--module=VALUE', 'Set the application module context for generator commands.'],
            ],
        ), $output);
        $output->writeln();
    }

    private function renderConventions(Output $output): void
    {
        $output->writelnFormatted('Input Conventions:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);
        $this->tables->render(ConsoleTable::make(
            ['Syntax', 'Meaning'],
            [
                ['<name>', 'Required argument.'],
                ['[name]', 'Optional argument.'],
                ['[options]', 'Optional flags or named values.'],
                ['--flag', 'Boolean option.'],
                ['--name=VALUE', 'Named value option.'],
                ['--tag=VALUE...', 'Repeatable named value option.'],
                ['--', 'Stop parsing options and treat following tokens as arguments.'],
            ],
        ), $output);
        $output->writeln();
    }

    private function renderWorkflows(Output $output): void
    {
        $output->writelnFormatted('Common Workflows:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);
        $this->tables->render(ConsoleTable::make(
            ['Task', 'Commands'],
            [
                ['Inspect project state', 'lpwork about, lpwork health:check, lpwork config:show'],
                ['Run application tests', 'lpwork test, lpwork test --module=Blog'],
                ['Run framework tests', 'lpwork test:lpwork, lpwork test:lpwork --browser, lpwork check'],
                ['Work with routes and caches', 'lpwork route:list, lpwork cache:rebuild'],
                ['Run frontend tooling', 'lpwork frontend:dev, lpwork frontend:build, lpwork frontend:check'],
                ['Run browser checks', 'lpwork browser:install, lpwork browser:test'],
                ['Work with database state', 'lpwork migrate:status, lpwork migrate, lpwork migrate:rollback'],
                ['Generate application code', 'lpwork make:module Blog, lpwork --module=Blog make:controller HomeController'],
                ['Install shell integration', 'lpwork completion:install'],
            ],
        ), $output);
        $output->writeln();
    }

    private function renderNextSteps(Output $output): void
    {
        $output->writelnFormatted('More Help:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);
        $output->writeln('  lpwork                 Show the compact command list.');
        $output->writeln('  lpwork <command> -h    Show arguments and options for one command.');
    }
}
