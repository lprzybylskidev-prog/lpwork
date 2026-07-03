<?php

declare(strict_types=1);

use LPWork\Console\CommandRegistry;
use Tests\support\testing\ApplicationTestHarness;
use Tests\support\testing\Cli\CliTestClient;

beforeEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterAll(function (): void {
    ApplicationTestHarness::removeDirectories();
});

it('registers maintenance commands with the application console', function (): void {
    $app = ApplicationTestHarness::fromProjectDefaults()->bootstrap(['lpwork', 'list']);
    $registry = $app->container()->make(CommandRegistry::class);

    expect($registry)->toBeInstanceOf(CommandRegistry::class);

    if (!$registry instanceof CommandRegistry) {
        return;
    }

    expect($registry->has('maintenance:down'))->toBeTrue()
        ->and($registry->has('maintenance:up'))->toBeTrue()
        ->and($registry->has('maintenance:status'))->toBeTrue();
});

it('turns maintenance mode on and off through CLI commands', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();
    $client = CliTestClient::forApplication($harness->bootstrap(['lpwork', 'maintenance:down']));

    $client->command('maintenance:down', '--retry=120')
        ->assertSuccessful()
        ->assertStdout("WARN Application is now in maintenance mode.\n")
        ->assertNoStderr();

    $client->command('maintenance:status')
        ->assertSuccessful()
        ->assertStdoutContains('WARN Maintenance mode is active.')
        ->assertStdoutContains('| Retry-After | 120');

    $client->command('maintenance:up')
        ->assertSuccessful()
        ->assertStdout("OK Application is now live.\n");

    $client->command('maintenance:status')
        ->assertSuccessful()
        ->assertStdout("OK Maintenance mode is inactive.\n");
});
