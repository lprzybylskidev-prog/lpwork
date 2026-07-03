<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Foundation\Application;
use Tests\support\config\AppConfigProvider;
use Tests\support\ConfigTestFiles;

beforeEach(function (): void {
    Config::reset();
});

afterEach(function (): void {
    Config::reset();
});

afterAll(function (): void {
    ConfigTestFiles::removeDirectories();
});

it('initializes config definitions', function (): void {
    $basePath = ConfigTestFiles::createDirectory();

    $provider = new AppConfigProvider(new Application($basePath));

    $provider->register(new LPWork\Container\Container());

    expect(Config::getString('app.name'))->toBe('LPWork');
});

it('loads cached configuration when the compiled cache exists', function (): void {
    $basePath = ConfigTestFiles::createDirectory();
    ConfigTestFiles::createConfig('storage/framework/cache/config.php', [
        'app' => [
            'name' => 'From cache',
        ],
    ], $basePath);

    $provider = new AppConfigProvider(new Application($basePath));

    $provider->register(new LPWork\Container\Container());

    expect(Config::getString('app.name'))->toBe('From cache');
});

it('loads normal configuration definitions when the compiled cache is missing', function (): void {
    $basePath = ConfigTestFiles::createDirectory();

    $provider = new AppConfigProvider(new Application($basePath));

    $provider->register(new LPWork\Container\Container());

    expect(Config::getString('app.name'))->toBe('LPWork');
});
