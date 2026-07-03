<?php

declare(strict_types=1);

use LPWork\Console\Commands\CompletionInstallCommand;
use LPWork\Console\Completion\CompletionInstaller;
use LPWork\Console\Input;
use LPWork\Console\Output;
use Tests\support\ConfigTestFiles;
use Tests\support\console\OutputStreams;

beforeEach(function (): void {
    ConfigTestFiles::resetDirectory();
});

afterAll(function (): void {
    ConfigTestFiles::removeDirectories();
});

it('installs completion for the detected shell and prints refresh instructions', function (): void {
    $home = ConfigTestFiles::directory();
    $streams = OutputStreams::create();
    $command = new CompletionInstallCommand(new CompletionInstaller(), homePath: $home, shellPath: '/bin/bash');

    $exitCode = $command->handle(
        new Input(['lpwork', 'completion:install']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(0)
        ->and($streams->stdout())->toContain('Installed bash completion in ' . $home . '/.bashrc.')
        ->and($streams->stdout())->toContain('Refresh this terminal now with:')
        ->and($streams->stdout())->toContain('source <(lpwork completion:generate bash)')
        ->and($streams->stderr())->toBe('')
        ->and(file_get_contents($home . '/.bashrc'))->toContain('completion:generate bash');
});

it('allows the shell to be selected explicitly', function (): void {
    $home = ConfigTestFiles::directory();
    $streams = OutputStreams::create();
    $command = new CompletionInstallCommand(new CompletionInstaller(), homePath: $home, shellPath: '/bin/bash');

    $exitCode = $command->handle(
        new Input(['lpwork', 'completion:install', 'zsh']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(0)
        ->and($streams->stdout())->toContain('Installed zsh completion in ' . $home . '/.zshrc.')
        ->and($streams->stdout())->toContain('eval "$(lpwork completion:generate zsh)"')
        ->and(file_get_contents($home . '/.zshrc'))->toContain('completion:generate zsh');
});

it('returns an error when the shell cannot be detected', function (): void {
    $streams = OutputStreams::create();
    $command = new CompletionInstallCommand(new CompletionInstaller(), homePath: ConfigTestFiles::directory(), shellPath: '');

    $exitCode = $command->handle(
        new Input(['lpwork', 'completion:install']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(1)
        ->and($streams->stderr())->toContain('Cannot detect the current shell.');
});
