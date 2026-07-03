<?php

declare(strict_types=1);

use LPWork\Config\EnvironmentConfigurationValidator;
use LPWork\Config\EnvironmentRequirement;
use LPWork\Config\EnvironmentRequirementRegistry;
use LPWork\Environment\Environment;
use Tests\support\EnvironmentTestFiles;

beforeEach(function (): void {
    Environment::reset();
    EnvironmentTestFiles::resetFile();
});

afterEach(function (): void {
    Environment::reset();
});

afterAll(function (): void {
    EnvironmentTestFiles::removeFiles();
});

it('reports missing empty and invalid required environment values without exposing secrets', function (): void {
    $path = EnvironmentTestFiles::file();
    EnvironmentTestFiles::appendValue('APP_NAME', '');
    EnvironmentTestFiles::appendValue('APP_DEBUG', 'sometimes');

    Environment::init($path);

    $report = new EnvironmentConfigurationValidator(new EnvironmentRequirementRegistry([
        EnvironmentRequirement::nonEmptyString('APP_NAME'),
        EnvironmentRequirement::bool('APP_DEBUG'),
        EnvironmentRequirement::string('APP_KEY'),
    ]))->validate();

    expect($report->isValid())->toBeFalse()
        ->and($report->checked)->toBe(3)
        ->and($report->exitCode())->toBe(1)
        ->and(array_map(static fn($issue): string => $issue->key, $report->issues()))
        ->toBe(['APP_NAME', 'APP_DEBUG', 'APP_KEY'])
        ->and(array_map(static fn($issue): string => $issue->message, $report->issues()))
        ->toBe([
            'Required environment value is empty.',
            'Value cannot be parsed as bool.',
            'Missing required environment value.',
        ]);
});

it('validates only requirements whose environment condition applies', function (): void {
    $path = EnvironmentTestFiles::file();
    EnvironmentTestFiles::appendValue('DB_CONNECTION', 'sqlite');
    EnvironmentTestFiles::appendValue('DB_SQLITE_DATABASE', 'storage/database.sqlite');

    Environment::init($path);

    $report = new EnvironmentConfigurationValidator(new EnvironmentRequirementRegistry([
        EnvironmentRequirement::string('DB_CONNECTION'),
        EnvironmentRequirement::string('DB_SQLITE_DATABASE')->when('DB_CONNECTION', 'sqlite'),
        EnvironmentRequirement::string('DB_MYSQL_HOST')->when('DB_CONNECTION', 'mysql'),
    ]))->validate();

    expect($report->isValid())->toBeTrue()
        ->and($report->checked)->toBe(2)
        ->and($report->issues())->toBe([]);
});
