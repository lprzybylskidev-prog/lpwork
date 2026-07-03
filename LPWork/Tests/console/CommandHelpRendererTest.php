<?php

declare(strict_types=1);

use LPWork\Console\CommandHelpRenderer;
use LPWork\Console\Output;
use Tests\support\console\DescribedCommand;
use Tests\support\console\OutputStreams;

it('renders help for a command with declared arguments and options', function (): void {
    $streams = OutputStreams::create();

    new CommandHelpRenderer()->render(
        new DescribedCommand(),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($streams->stdout())->toContain('users:import')
        ->and($streams->stdout())->toContain('Import users.')
        ->and($streams->stdout())->toContain('lpwork users:import <file> [mode] [options]')
        ->and($streams->stdout())->toContain('Arguments:')
        ->and($streams->stdout())->toContain('file  CSV file path.')
        ->and($streams->stdout())->toContain('Options:')
        ->and($streams->stdout())->toContain('-h, --help')
        ->and($streams->stdout())->toContain('-f, --force')
        ->and($streams->stdout())->toContain('--tag=VALUE...')
        ->and($streams->stderr())->toBe('');
});
