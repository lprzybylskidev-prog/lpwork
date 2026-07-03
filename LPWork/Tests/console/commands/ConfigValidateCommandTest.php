<?php

declare(strict_types=1);

use LPWork\Bootstrap\Bootstrap;
use LPWork\Config\Config;
use LPWork\Config\EnvironmentConfigurationValidator;
use LPWork\Config\EnvironmentRequirement;
use LPWork\Config\EnvironmentRequirementRegistry;
use LPWork\Console\Commands\ConfigValidateCommand;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Environment\Environment;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\console\OutputStreams;
use Tests\support\EnvironmentTestFiles;
use Tests\support\testing\Cli\CliTestClient;

beforeEach(function (): void {
    Environment::reset();
    Config::reset();
    EnvironmentTestFiles::resetFile();
});

afterEach(function (): void {
    Environment::reset();
    Config::reset();
});

afterAll(function (): void {
    EnvironmentTestFiles::removeFiles();
    ApplicationTestEnvironment::removeDirectories();
});

it('renders a successful configuration validation report', function (): void {
    $path = EnvironmentTestFiles::file();
    EnvironmentTestFiles::appendValue('APP_NAME', 'LPWork');
    EnvironmentTestFiles::appendValue('APP_DEBUG', true);
    Environment::init($path);

    $streams = OutputStreams::create();
    $command = new ConfigValidateCommand(new EnvironmentConfigurationValidator(new EnvironmentRequirementRegistry([
        EnvironmentRequirement::nonEmptyString('APP_NAME'),
        EnvironmentRequirement::bool('APP_DEBUG'),
    ])));

    $exitCode = $command->handle(
        new Input(['lpwork', 'config:validate']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(0)
        ->and($streams->stdout())->toContain('Configuration: valid')
        ->and($streams->stdout())->toContain('Summary: 2 checked, 0 failed')
        ->and($streams->stdout())->toContain('OK Required environment values are present and parseable.')
        ->and($streams->stderr())->toBe('');
});

it('renders invalid configuration values as a diagnostics table', function (): void {
    $path = EnvironmentTestFiles::file();
    EnvironmentTestFiles::appendValue('APP_DEBUG', 'nope');
    Environment::init($path);

    $streams = OutputStreams::create();
    $command = new ConfigValidateCommand(new EnvironmentConfigurationValidator(new EnvironmentRequirementRegistry([
        EnvironmentRequirement::string('APP_NAME'),
        EnvironmentRequirement::bool('APP_DEBUG'),
    ])));

    $exitCode = $command->handle(
        new Input(['lpwork', 'config:validate']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    expect($exitCode)->toBe(1)
        ->and($streams->stdout())->toContain('Configuration: invalid')
        ->and($streams->stdout())->toContain('| APP_NAME  | string   | failed | Missing required environment value. |')
        ->and($streams->stdout())->toContain('| APP_DEBUG | bool     | failed | Value cannot be parsed as bool.     |')
        ->and($streams->stderr())->toBe('');
});

it('boots config validation without requiring a valid application key first', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->setEnvValue('APP_KEY', '');

    $app = Bootstrap::initForConsole($environment->basePath(), ['lpwork', 'config:validate']);
    $result = CliTestClient::forApplication($app)->command('config:validate');

    $result->assertExitCode(1)
        ->assertStdoutContains('Configuration: invalid')
        ->assertStdoutContains('APP_KEY')
        ->assertStdoutContains('Required environment value is empty.');
});
