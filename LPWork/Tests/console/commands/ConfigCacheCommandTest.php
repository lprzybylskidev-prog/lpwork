<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Config\ConfigCache;
use LPWork\Config\ConfigCacheRebuilder;
use LPWork\Config\ConfigCompiledCache;
use LPWork\Config\ConfigSourceFiles;
use LPWork\Console\Commands\ConfigCacheCommand;
use LPWork\Console\Input;
use LPWork\Console\Output;
use Tests\support\ConfigTestFiles;
use Tests\support\console\OutputStreams;

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

it('rebuilds the compiled config cache and reports success', function (): void {
    $basePath = ConfigTestFiles::directory();
    $sourceFile = ConfigTestFiles::createConfig('app.php', ['name' => 'LPWork'], $basePath);
    $cache = new ConfigCache($basePath, 'cache/config.php');
    $streams = OutputStreams::create();
    $command = new ConfigCacheCommand(
        new ConfigCompiledCache(
            $cache,
            new ConfigCacheRebuilder($cache, new ConfigSourceFiles([$sourceFile])),
        ),
    );

    Config::initFiles([$sourceFile]);

    $exitCode = $command->handle(
        new Input(['lpwork', 'config:cache']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    );

    Config::reset();
    $cache->load();

    expect($exitCode)->toBe(0)
        ->and($streams->stdout())->toContain('Configuration cache rebuilt successfully.')
        ->and($streams->stderr())->toBe('')
        ->and(Config::getString('app.name'))->toBe('LPWork');
});
