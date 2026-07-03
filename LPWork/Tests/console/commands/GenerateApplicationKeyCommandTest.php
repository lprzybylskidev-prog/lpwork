<?php

declare(strict_types=1);

use LPWork\Console\Commands\GenerateApplicationKeyCommand;
use LPWork\Security\ApplicationKey;
use Tests\support\security\EnvironmentApplicationKeyFile;
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

it('generates and stores APP_KEY when it is empty', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults()
        ->setEnvValue('APP_KEY', '');

    CliTestClient::forApplication($harness->bootstrap(['lpwork', 'key:generate']))
        ->command('key:generate')
        ->assertSuccessful()
        ->assertStdout("OK APP_KEY generated successfully.\n")
        ->assertNoStderr();

    $storedKey = new EnvironmentApplicationKeyFile($harness->envPath())->key();

    expect($storedKey)->toStartWith('base64:')
        ->and(ApplicationKey::fromString($storedKey))->toBeInstanceOf(ApplicationKey::class);
});

it('refuses to overwrite an existing APP_KEY without force', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();
    $keyFile = new EnvironmentApplicationKeyFile($harness->envPath());
    $existingKey = $keyFile->key();

    CliTestClient::forApplication($harness->bootstrap(['lpwork', 'key:generate']))
        ->command('key:generate')
        ->assertExitCode(1)
        ->assertNoStdout()
        ->assertStderr("ERROR APP_KEY already has a value. Use --force to overwrite it.\n");

    expect($keyFile->key())->toBe($existingKey);
});

it('refuses to generate APP_KEY in production without force', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults()
        ->setEnvValue('APP_ENV', 'production')
        ->setEnvValue('APP_KEY', '');

    CliTestClient::forApplication($harness->bootstrap(['lpwork', 'key:generate']))
        ->command('key:generate')
        ->assertExitCode(1)
        ->assertNoStdout()
        ->assertStderr("Refusing to generate APP_KEY in production without --force.\n");

    expect(new EnvironmentApplicationKeyFile($harness->envPath())->key())->toBe('');
});

it('overwrites APP_KEY when force is present', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();
    $keyFile = new EnvironmentApplicationKeyFile($harness->envPath());
    $existingKey = $keyFile->key();

    CliTestClient::forApplication($harness->bootstrap(['lpwork', 'key:generate', '--force']))
        ->command('key:generate', '--force')
        ->assertSuccessful()
        ->assertStdout("OK APP_KEY generated successfully.\n")
        ->assertNoStderr();

    $storedKey = $keyFile->key();

    expect($storedKey)->not->toBe($existingKey)
        ->and(ApplicationKey::fromString($storedKey))->toBeInstanceOf(ApplicationKey::class);
});

it('allows forced APP_KEY generation in production', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults()
        ->setEnvValue('APP_ENV', 'production')
        ->setEnvValue('APP_KEY', '');

    CliTestClient::forApplication($harness->bootstrap(['lpwork', 'key:generate', '--force']))
        ->command('key:generate', '--force')
        ->assertSuccessful()
        ->assertStdout("OK APP_KEY generated successfully.\n")
        ->assertNoStderr();

    expect(ApplicationKey::fromString(new EnvironmentApplicationKeyFile($harness->envPath())->key()))->toBeInstanceOf(ApplicationKey::class);
});

it('is registered as a framework command', function (): void {
    $harness = ApplicationTestHarness::fromProjectDefaults();

    $app = $harness->bootstrap();
    $commands = $app->container()->make(LPWork\Console\CommandRegistry::class);

    expect($commands)->toBeInstanceOf(LPWork\Console\CommandRegistry::class);

    if (!$commands instanceof LPWork\Console\CommandRegistry) {
        return;
    }

    expect($commands->get('key:generate'))->toBeInstanceOf(GenerateApplicationKeyCommand::class);
});
