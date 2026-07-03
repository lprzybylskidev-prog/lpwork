<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Config\ConfigCache;
use LPWork\Config\ConfigCacheRebuilder;
use LPWork\Config\ConfigSourceFiles;
use Tests\support\ConfigTestFiles;

beforeEach(function (): void {
    Config::reset();
    ConfigTestFiles::resetDirectory();
});

afterEach(function (): void {
    Config::reset();
});

afterAll(function (): void {
    ConfigTestFiles::removeDirectories();
});

it('rebuilds the compiled config cache from source config files', function (): void {
    $basePath = ConfigTestFiles::directory();
    $sourceFile = ConfigTestFiles::createConfig('app.php', ['name' => 'Fresh'], $basePath);
    ConfigTestFiles::createConfig('cache/config.php', [
        'app' => [
            'name' => 'Stale',
        ],
    ], $basePath);

    $cache = new ConfigCache($basePath, 'cache/config.php');
    Config::initCached($cache->path());

    expect(Config::getString('app.name'))->toBe('Stale');

    $rebuilder = new ConfigCacheRebuilder($cache, new ConfigSourceFiles([$sourceFile]));
    $rebuilder->rebuild();
    Config::reset();
    $cache->load();

    expect(Config::getString('app.name'))->toBe('Fresh');
});
