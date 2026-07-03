<?php

declare(strict_types=1);

use LPWork\Console\CommandRegistry;
use PHPUnit\Framework\AssertionFailedError;
use Tests\support\console\DescribedCommand;
use Tests\support\security\EnvironmentApplicationKeyFile;
use Tests\support\testing\ApplicationTestHarness;
use Tests\support\testing\Cli\CliTestClient;
use Tests\support\testing\Cli\InteractiveGreetingCommand;
use Tests\support\testing\Cli\TestConsoleResult;

beforeEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterEach(function (): void {
    ApplicationTestHarness::resetFrameworkState();
});

afterAll(function (): void {
    ApplicationTestHarness::removeDirectories();
});

it('executes commands through the real CLI kernel and captures output', function (): void {
    $harness = ApplicationTestHarness::create();
    $registry = new CommandRegistry();
    $registry->add(new DescribedCommand());
    $harness->container()->instance(CommandRegistry::class, $registry);

    CliTestClient::forApplication($harness->application())
        ->command('users:import')
        ->assertSuccessful()
        ->assertStdout("imported\n")
        ->assertNoStderr();
});

it('asserts exit codes and stderr output', function (): void {
    $harness = ApplicationTestHarness::create();

    $result = CliTestClient::forApplication($harness->application())
        ->command('missing');

    $result
        ->assertFailed()
        ->assertExitCode(1)
        ->assertNoStdout()
        ->assertStderr("Command not found: missing\n");

    expect(fn(): TestConsoleResult => $result->assertSuccessful())
        ->toThrow(AssertionFailedError::class);
});

it('provides controlled input streams to commands resolved through the container', function (): void {
    $harness = ApplicationTestHarness::create();

    CliTestClient::forApplication($harness->application())
        ->withInput("Ada\n")
        ->withCommand(InteractiveGreetingCommand::class)
        ->command('interactive:greet')
        ->assertSuccessful()
        ->assertStdout("Name [LPWork]: Hello Ada\n")
        ->assertNoStderr();
});

it('covers production-sensitive commands through the CLI safety middleware', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults()
        ->setEnvValue('APP_ENV', 'production')
        ->setEnvValue('APP_KEY', '');
    $app = $harness->bootstrap(['lpwork', 'key:generate']);

    CliTestClient::forApplication($app)
        ->command('key:generate')
        ->assertFailed()
        ->assertNoStdout()
        ->assertStderr("Refusing to generate APP_KEY in production without --force.\n");

    expect(new EnvironmentApplicationKeyFile($harness->envPath())->key())->toBe('');
});

it('allows forced production-sensitive commands through the real CLI kernel', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults()
        ->setEnvValue('APP_ENV', 'production')
        ->setEnvValue('APP_KEY', '');
    $app = $harness->bootstrap(['lpwork', 'key:generate', '--force']);

    CliTestClient::forApplication($app)
        ->command('key:generate', '--force')
        ->assertSuccessful()
        ->assertStdout("OK APP_KEY generated successfully.\n")
        ->assertNoStderr();

    expect(new EnvironmentApplicationKeyFile($harness->envPath())->key())->toStartWith('base64:');
});
