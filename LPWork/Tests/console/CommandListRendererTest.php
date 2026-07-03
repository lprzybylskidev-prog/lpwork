<?php

declare(strict_types=1);

use LPWork\Console\CommandListRenderer;
use LPWork\Console\CommandRegistry;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\HiddenCommand;
use LPWork\Console\Input;
use LPWork\Console\Output;
use Tests\support\console\OutputStreams;
use Tests\support\console\TestCommand;

it('renders a console landing screen without commands', function (): void {
    $streams = OutputStreams::create();

    new CommandListRenderer()->render(
        new CommandRegistry(),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($streams->stdout())->toContain('LPWork Console')
        ->and($streams->stdout())->toContain('Usage:')
        ->and($streams->stdout())->toContain('lpwork <command> [arguments] [options]')
        ->and($streams->stdout())->toContain('No commands registered yet.')
        ->and($streams->stderr())->toBe('');
});

it('renders the console brand with the LPWork blue color', function (): void {
    $streams = OutputStreams::create();

    new CommandListRenderer()->render(
        new CommandRegistry(),
        new Output($streams->stdout, $streams->stderr),
    );

    expect($streams->stdout())->toContain("\033[1;38;2;66;136;206mLPWork Console\033[0m");
});

it('renders registered commands', function (): void {
    $streams = OutputStreams::create();
    $commands = new CommandRegistry();

    $commands->add(new TestCommand('cache:clear', 'Clear cache.'));
    $commands->add(new TestCommand('about', 'Display application information.'));

    new CommandListRenderer()->render(
        $commands,
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($streams->stdout())->toContain('Available commands:')
        ->and($streams->stdout())->toContain("Core\n")
        ->and($streams->stdout())->toContain("Cache\n")
        ->and($streams->stdout())->toContain('| Command | Description                      |')
        ->and($streams->stdout())->toContain('| about   | Display application information. |')
        ->and($streams->stdout())->toContain('| cache:clear | Clear cache. |');
});

it('does not render hidden internal commands', function (): void {
    $streams = OutputStreams::create();
    $commands = new CommandRegistry();

    $commands->add(new TestCommand('completion:install', 'Install completion.'));
    $commands->add(new class implements Command, HiddenCommand {
        public function name(): string
        {
            return 'completion:generate';
        }

        public function description(): string
        {
            return 'Generate completion.';
        }

        public function handle(Input $input, Output $output): int
        {
            return 0;
        }
    });

    new CommandListRenderer()->render(
        $commands,
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($streams->stdout())->toContain('| completion:install | Install completion. |')
        ->and($streams->stdout())->not->toContain('completion:generate');
});
