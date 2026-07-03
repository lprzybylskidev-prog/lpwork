<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Config\ConfigCache;
use LPWork\Config\Exceptions\InvalidFileException;
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

it('owns the compiled config cache path', function (): void {
    $basePath = ConfigTestFiles::directory();

    expect(new ConfigCache($basePath)->path())
        ->toBe($basePath . '/storage/framework/cache/config.php');
});

it('accepts an absolute compiled config cache path', function (): void {
    $path = ConfigTestFiles::directory() . '/compiled.php';

    expect(new ConfigCache('/unused', $path)->path())->toBe($path);
});

it('detects whether the compiled config cache exists', function (): void {
    $basePath = ConfigTestFiles::directory();
    $cache = new ConfigCache($basePath, 'cache/config.php');

    expect($cache->exists())->toBeFalse();

    ConfigTestFiles::createConfig('cache/config.php', ['app' => ['name' => 'Cached']], $basePath);

    expect($cache->exists())->toBeTrue();
});

it('writes the compiled config cache', function (): void {
    $directory = ConfigTestFiles::directory();
    $file = ConfigTestFiles::createConfig('app.php', ['name' => 'LPWork'], $directory);
    $cache = new ConfigCache($directory, 'cache/config.php');

    try {
        Config::initFiles([$file]);
        $cache->write();
        Config::reset();
        unlink($file);

        $cache->load();

        expect(Config::getString('app.name'))->toBe('LPWork');
    } finally {
        if (is_file($file)) {
            unlink($file);
        }
    }
});

it('reports corrupted compiled config cache files explicitly', function (): void {
    $basePath = ConfigTestFiles::directory();
    mkdir($basePath . '/cache', 0o777, true);
    file_put_contents($basePath . '/cache/config.php', "<?php\n\nreturn 'broken';\n");
    Config::reset();

    try {
        expect(fn() => new ConfigCache($basePath, 'cache/config.php')->load())
            ->toThrow(InvalidFileException::class, $basePath . '/cache/config.php');
    } finally {
        Config::reset();
    }
});

it('clears the compiled config cache', function (): void {
    $basePath = ConfigTestFiles::directory();
    $cache = new ConfigCache($basePath, 'cache/config.php');

    ConfigTestFiles::createConfig('cache/config.php', ['app' => ['name' => 'Cached']], $basePath);

    $cache->clear();

    expect($cache->exists())->toBeFalse();
});

it('ignores clearing a missing compiled config cache', function (): void {
    $cache = new ConfigCache(ConfigTestFiles::directory(), 'cache/config.php');

    $cache->clear();

    expect($cache->exists())->toBeFalse();
});
