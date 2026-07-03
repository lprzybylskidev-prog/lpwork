<?php

declare(strict_types=1);

use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\Exceptions\InvalidLogChannelException;
use LPWork\Logging\Exceptions\InvalidLogConfigException;
use LPWork\Logging\Exceptions\InvalidLogDriverException;
use LPWork\Logging\Exceptions\InvalidLogFormatterException;
use LPWork\Logging\Exceptions\MissingLogConfigException;
use LPWork\Logging\LogDriverFactory;
use LPWork\Logging\LogManager;
use LPWork\Storage\StorageManager;

it('returns the configured default channel', function (): void {
    $manager = new LogManager([
        'default' => 'app',
        'channels' => [
            'app' => [
                'driver' => 'file',
                'path' => 'storage/logs/app.log',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect($manager->default())->toBeInstanceOf(Logger::class);
});

it('caches created channels', function (): void {
    $manager = new LogManager([
        'default' => 'app',
        'channels' => [
            'app' => [
                'driver' => 'file',
                'path' => 'storage/logs/app.log',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect($manager->channel('app'))->toBe($manager->channel('app'));
});

it('throws when channel is missing', function (): void {
    $manager = new LogManager([
        'default' => 'app',
        'channels' => [],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): Logger => $manager->channel('missing'))
        ->toThrow(InvalidLogChannelException::class);
});

it('throws when driver is unsupported', function (): void {
    $manager = new LogManager([
        'default' => 'app',
        'channels' => [
            'app' => [
                'driver' => 'database',
                'path' => 'storage/logs/app.log',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): Logger => $manager->channel('app'))
        ->toThrow(InvalidLogDriverException::class);
});

it('throws when formatter is unsupported', function (): void {
    $manager = new LogManager([
        'default' => 'app',
        'channels' => [
            'app' => [
                'driver' => 'file',
                'path' => 'storage/logs/app.log',
                'format' => 'xml',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): Logger => $manager->channel('app'))
        ->toThrow(InvalidLogFormatterException::class);
});

it('throws when rotation is unsupported', function (): void {
    $manager = new LogManager([
        'default' => 'app',
        'channels' => [
            'app' => [
                'driver' => 'file',
                'path' => 'storage/logs/app.log',
                'rotation' => 'weekly',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): Logger => $manager->channel('app'))
        ->toThrow(InvalidLogConfigException::class);
});

it('creates fallback drivers from explicit primary and fallback declarations', function (): void {
    $basePath = Tests\support\LoggingTestFiles::createDirectory();

    try {
        $manager = new LogManager([
            'default' => 'app',
            'channels' => [
                'app' => [
                    'driver' => 'fallback',
                    'primary' => [
                        'driver' => 'file',
                        'path' => 'storage/logs/app.log',
                        'format' => 'line',
                    ],
                    'fallback' => [
                        'driver' => 'file',
                        'path' => 'storage/logs/fallback.log',
                        'format' => 'line',
                    ],
                ],
            ],
        ], $basePath);

        $manager->default()->info('Stored through explicit fallback strategy');

        expect(file_get_contents($basePath . '/storage/logs/app.log'))->toContain('Stored through explicit fallback strategy')
            ->and($basePath . '/storage/logs/fallback.log')->not->toBeFile();
    } finally {
        Tests\support\LoggingTestFiles::removeDirectory($basePath);
    }
});

it('uses configured storage disks for file log channels', function (): void {
    $storage = new StorageManager([
        'default' => 'memory',
        'disks' => [
            'memory' => [
                'driver' => 'memory',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());
    $manager = new LogManager([
        'default' => 'app',
        'channels' => [
            'app' => [
                'driver' => 'file',
                'disk' => 'memory',
                'path' => 'logs/app.log',
                'format' => 'line',
            ],
        ],
    ], \Tests\support\ProjectPaths::root(), new LogDriverFactory(\Tests\support\ProjectPaths::root(), storage: $storage));

    $manager->default()->info('Stored through storage');

    expect($storage->disk('memory')->get('logs/app.log'))->toContain('Stored through storage');
});

it('throws when fallback driver declarations are incomplete', function (): void {
    $manager = new LogManager([
        'default' => 'app',
        'channels' => [
            'app' => [
                'driver' => 'fallback',
                'fallback' => [
                    'driver' => 'file',
                    'path' => 'storage/logs/fallback.log',
                ],
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): Logger => $manager->channel('app'))
        ->toThrow(MissingLogConfigException::class);
});

it('returns stack channels that forward records to configured channels', function (): void {
    $basePath = Tests\support\LoggingTestFiles::createDirectory();

    try {
        $manager = new LogManager([
            'default' => 'stack',
            'channels' => [
                'stack' => [
                    'driver' => 'stack',
                    'channels' => ['app', 'error'],
                ],
                'app' => [
                    'driver' => 'file',
                    'path' => 'storage/logs/app.log',
                    'format' => 'line',
                ],
                'error' => [
                    'driver' => 'file',
                    'path' => 'storage/logs/error.log',
                    'format' => 'line',
                ],
            ],
        ], $basePath);

        $manager->default()->error('Stacked {id}', ['id' => 'abc']);

        expect(file_get_contents($basePath . '/storage/logs/app.log'))->toContain('Stacked abc')
            ->and(file_get_contents($basePath . '/storage/logs/error.log'))->toContain('Stacked abc');
    } finally {
        Tests\support\LoggingTestFiles::removeDirectory($basePath);
    }
});

it('applies minimum levels from channel config', function (): void {
    $basePath = Tests\support\LoggingTestFiles::createDirectory();

    try {
        $manager = new LogManager([
            'default' => 'app',
            'channels' => [
                'app' => [
                    'driver' => 'file',
                    'path' => 'storage/logs/app.log',
                    'format' => 'line',
                    'level' => 'warning',
                ],
            ],
        ], $basePath);

        $manager->default()->info('Hidden');
        $manager->default()->warning('Visible');

        expect(file_get_contents($basePath . '/storage/logs/app.log'))->not->toContain('Hidden')
            ->toContain('Visible');
    } finally {
        Tests\support\LoggingTestFiles::removeDirectory($basePath);
    }
});

it('throws when channel level is unsupported', function (): void {
    $manager = new LogManager([
        'default' => 'app',
        'channels' => [
            'app' => [
                'driver' => 'file',
                'path' => 'storage/logs/app.log',
                'level' => 'verbose',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): Logger => $manager->channel('app'))
        ->toThrow(InvalidLogConfigException::class);
});

it('throws when stack channel declarations are invalid', function (): void {
    $manager = new LogManager([
        'default' => 'stack',
        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['app', 123],
            ],
            'app' => [
                'driver' => 'file',
                'path' => 'storage/logs/app.log',
            ],
        ],
    ], \Tests\support\ProjectPaths::root());

    expect(fn(): Logger => $manager->default())
        ->toThrow(InvalidLogConfigException::class);
});
