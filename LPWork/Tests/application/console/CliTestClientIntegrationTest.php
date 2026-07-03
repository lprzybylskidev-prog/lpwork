<?php

declare(strict_types=1);

use LPWork\Console\CommandDiscovery;
use LPWork\Console\CommandRegistry;
use LPWork\Console\ConsoleMiddlewareStack;
use Tests\support\console\FirstConsoleMiddleware;
use Tests\support\security\EnvironmentApplicationKeyFile;
use Tests\support\testing\ApplicationTestHarness;
use Tests\support\testing\Cli\ApplicationCliIntegrationCommand;
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

it('executes application CLI commands through discovery middleware input parsing and captured output', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();
    $app = $harness->bootstrap(['lpwork', 'app:cli']);

    $commands = $app->container()->make(CommandRegistry::class);
    $discovery = $app->container()->make(CommandDiscovery::class);
    $middleware = $app->container()->make(ConsoleMiddlewareStack::class);

    expect($commands)->toBeInstanceOf(CommandRegistry::class)
        ->and($discovery)->toBeInstanceOf(CommandDiscovery::class)
        ->and($middleware)->toBeInstanceOf(ConsoleMiddlewareStack::class);

    if (!$commands instanceof CommandRegistry || !$discovery instanceof CommandDiscovery || !$middleware instanceof ConsoleMiddlewareStack) {
        return;
    }

    $middleware->add(FirstConsoleMiddleware::class);

    foreach ($discovery->discover([
        ApplicationCliIntegrationCommand::class,
    ]) as $command) {
        $commands->add($command);
    }

    $client = CliTestClient::forApplication($app);

    $client->command('app:cli', '--help')
        ->assertSuccessful()
        ->assertStdoutContains('Application CLI integration command.')
        ->assertStdoutContains('lpwork app:cli <subject> [mode] [options]')
        ->assertNoStderr();

    $client->command('app:cli', 'report', 'dry-run', '--path=App', '--tag=alpha', '--tag', 'beta', '--force')
        ->assertSuccessful()
        ->assertStdout(implode("\n", [
            'first-before',
            'second-before',
            'arguments=report|dry-run',
            'path=App',
            'tags=alpha|beta',
            'force=yes',
            'second-after',
            'first-after',
            '',
        ]))
        ->assertNoStderr();

    $client->command('app:cli', 'report', '--fail')
        ->assertExitCode(7)
        ->assertStdoutContains('first-before')
        ->assertStdoutContains('second-after')
        ->assertStderr("application command failed\n");
});

it('covers production-sensitive application CLI behavior through the CLI test client', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults()
        ->setEnvValue('APP_ENV', 'production')
        ->setEnvValue('APP_KEY', '');

    $client = CliTestClient::forApplication($harness->bootstrap(['lpwork', 'key:generate']));

    $client->command('key:generate')
        ->assertFailed()
        ->assertNoStdout()
        ->assertStderr("Refusing to generate APP_KEY in production without --force.\n");

    expect(new EnvironmentApplicationKeyFile($harness->envPath())->key())->toBe('');

    $client->command('key:generate', '--force')
        ->assertSuccessful()
        ->assertStdout("OK APP_KEY generated successfully.\n")
        ->assertNoStderr();

    expect(new EnvironmentApplicationKeyFile($harness->envPath())->key())->toStartWith('base64:');
});
