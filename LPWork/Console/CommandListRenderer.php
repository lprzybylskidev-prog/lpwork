<?php

declare(strict_types=1);

namespace LPWork\Console;

use function array_key_exists;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\HiddenCommand;
use LPWork\Console\Enums\ConsoleColor;
use LPWork\Console\Enums\ConsoleStyle;

use function str_starts_with;

/**
 * Renders command list renderer output.
 */
final class CommandListRenderer
{
    private const CATEGORY_ORDER = [
        'Core',
        'Development',
        'Configuration',
        'Cache',
        'Database',
        'Routing',
        'Queue',
        'Scheduler',
        'Maintenance',
        'Generators',
        'Views and translations',
        'Shell',
        'Framework',
    ];

    /**
     * Creates a new CommandListRenderer instance.
     */
    public function __construct(
        private readonly ?ConsoleBootstrapNotice $notice = null,
        private readonly ConsoleTableRenderer $tables = new ConsoleTableRenderer(),
    ) {}

    /**
     * Renders this component into its output representation.
     */
    public function render(CommandRegistry $commands, Output $output): void
    {
        $this->renderHeader($output);
        $this->renderNotice($output);
        $this->renderUsage($output);
        $this->renderCommands($commands, $output);
    }

    private function renderHeader(Output $output): void
    {
        $output->writelnFormatted(' _      ____  __        __         _    ', ConsoleColor::LpworkBlue, styles: [ConsoleStyle::Bold]);
        $output->writelnFormatted('| |    |  _ \ \ \      / /__  _ __| | __', ConsoleColor::LpworkBlue, styles: [ConsoleStyle::Bold]);
        $output->writelnFormatted('| |    | |_) | \ \ /\ / / _ \| \'__| |/ /', ConsoleColor::LpworkBlue, styles: [ConsoleStyle::Bold]);
        $output->writelnFormatted('| |___ |  __/   \ V  V / (_) | |  |   < ', ConsoleColor::LpworkBlue, styles: [ConsoleStyle::Bold]);
        $output->writelnFormatted('|_____||_|       \_/\_/ \___/|_|  |_|\_\\', ConsoleColor::LpworkBlue, styles: [ConsoleStyle::Bold]);
        $output->writeln();
        $output->writelnFormatted('LPWork Console', ConsoleColor::LpworkBlue, styles: [ConsoleStyle::Bold]);
        $output->writeln();
    }

    private function renderNotice(Output $output): void
    {
        if ($this->notice === null) {
            return;
        }

        $output->writelnFormatted($this->notice->message, ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);
        $output->writelnFormatted(
            'Only config:clear and config:cache are available until the configuration cache is rebuilt.',
            ConsoleColor::Yellow,
        );
        $output->writeln();
    }

    private function renderUsage(Output $output): void
    {
        $output->writelnFormatted('Usage:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);
        $output->writeln('  lpwork <command> [arguments] [options]');
        $output->writeln();
    }

    private function renderCommands(CommandRegistry $commands, Output $output): void
    {
        $registeredCommands = $this->visibleCommands($commands);

        if ($registeredCommands === []) {
            $output->writelnFormatted('No commands registered yet.', ConsoleColor::Gray);

            return;
        }

        $output->writelnFormatted('Available commands:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);

        foreach ($this->groupCommands($registeredCommands) as $category => $commandsInCategory) {
            $output->writelnFormatted($category, ConsoleColor::Cyan, styles: [ConsoleStyle::Bold]);

            $rows = [];

            foreach ($commandsInCategory as $command) {
                $rows[] = [$command->name(), $command->description()];
            }

            $this->tables->render(ConsoleTable::make(
                ['Command', 'Description'],
                $rows,
            ), $output);
            $output->writeln();
        }
    }

    /**
     * @return array<string, Command>
     */
    private function visibleCommands(CommandRegistry $commands): array
    {
        $visible = [];

        foreach ($commands->all() as $name => $command) {
            if ($command instanceof HiddenCommand) {
                continue;
            }

            $visible[$name] = $command;
        }

        return $visible;
    }

    /**
     * @param array<string, Command> $commands
     *
     * @return array<string, list<Command>>
     */
    private function groupCommands(array $commands): array
    {
        $groups = [];

        foreach (self::CATEGORY_ORDER as $category) {
            $groups[$category] = [];
        }

        foreach ($commands as $command) {
            $groups[$this->category($command->name())][] = $command;
        }

        foreach ($groups as $category => $commandsInCategory) {
            if ($commandsInCategory === []) {
                unset($groups[$category]);
            }
        }

        return $groups;
    }

    private function category(string $name): string
    {
        if ($name === 'about' || $name === 'health:check' || $name === 'key:generate') {
            return 'Core';
        }

        if ($name === 'check' || $name === 'coverage' || $name === 'format' || str_starts_with($name, 'test')) {
            return 'Development';
        }

        $prefix = str_contains($name, ':') ? explode(':', $name, 2)[0] : $name;

        $categories = [
            'cache' => 'Cache',
            'completion' => 'Shell',
            'config' => 'Configuration',
            'db' => 'Database',
            'maintenance' => 'Maintenance',
            'make' => 'Generators',
            'migrate' => 'Database',
            'queue' => 'Queue',
            'route' => 'Routing',
            'schedule' => 'Scheduler',
            'translation' => 'Views and translations',
            'view' => 'Views and translations',
        ];

        if (array_key_exists($prefix, $categories)) {
            return $categories[$prefix];
        }

        return 'Framework';
    }
}
