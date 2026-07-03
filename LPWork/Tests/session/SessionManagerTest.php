<?php

declare(strict_types=1);

use LPWork\Session\Contracts\SessionDriver;
use LPWork\Session\Drivers\InMemorySessionDriver;
use LPWork\Session\Drivers\PhpSessionDriver;
use LPWork\Session\Exceptions\InvalidSessionConfigException;
use LPWork\Session\Exceptions\InvalidSessionDriverException;
use LPWork\Session\Exceptions\MissingSessionConfigException;
use LPWork\Session\SessionManager;
use Tests\support\session\SessionConfig;

it('returns the configured default session driver', function (): void {
    $manager = new SessionManager(SessionConfig::php());

    expect($manager->default())->toBeInstanceOf(PhpSessionDriver::class)
        ->and($manager->default())->toBeInstanceOf(SessionDriver::class);
});

it('returns session drivers by name', function (): void {
    $manager = new SessionManager(SessionConfig::php());

    expect($manager->driver('php'))->toBeInstanceOf(PhpSessionDriver::class);
});

it('returns in-memory session drivers from configuration', function (): void {
    $manager = new SessionManager(SessionConfig::memory());

    expect($manager->default())->toBeInstanceOf(InMemorySessionDriver::class)
        ->and($manager->default())->toBeInstanceOf(SessionDriver::class);
});

it('persists lifecycle requests through the in-memory session driver', function (): void {
    $driver = new InMemorySessionDriver(['user_id' => 15]);
    $session = $driver->start();

    $session->regenerate();
    $driver->save($session);

    expect($driver->regenerations)->toBe(1)
        ->and($session->regenerationRequested())->toBeFalse();

    $session = $driver->start();
    $session->invalidate();
    $driver->save($session);

    expect($driver->invalidations)->toBe(1)
        ->and($driver->data())->toBe([
            '_flash' => [
                'new' => [],
                'old' => [],
            ],
        ])
        ->and($session->invalidationRequested())->toBeFalse();
});

it('caches created session drivers', function (): void {
    $manager = new SessionManager(SessionConfig::php());

    expect($manager->driver('php'))->toBe($manager->driver('php'));
});

it('throws when session driver config is missing', function (): void {
    expect(fn() => new SessionManager([])->default())
        ->toThrow(MissingSessionConfigException::class);
});

it('throws when session driver config is invalid', function (): void {
    expect(fn() => new SessionManager(['default' => '', 'drivers' => []])->default())
        ->toThrow(InvalidSessionConfigException::class);
});

it('throws when session driver is unsupported', function (): void {
    expect(fn() => new SessionManager(['default' => 'redis', 'drivers' => []])->default())
        ->toThrow(InvalidSessionDriverException::class);
});

it('throws when configured driver type is unsupported', function (): void {
    expect(fn() => new SessionManager([
        'default' => 'sessions',
        'drivers' => [
            'sessions' => [
                'driver' => 'missing',
            ],
        ],
    ])->default())->toThrow(InvalidSessionDriverException::class, 'Session driver is not supported: missing.');
});

it('validates redis session driver configuration', function (): void {
    expect(fn() => new SessionManager([
        'default' => 'sessions',
        'drivers' => [
            'sessions' => [
                'driver' => 'redis',
            ],
        ],
    ])->default())->toThrow(MissingSessionConfigException::class, 'Missing session configuration value: drivers.sessions.host.');
});

it('throws when php session driver config is invalid', function (): void {
    expect(fn() => new SessionManager(SessionConfig::withPhpConfig(['lifetime' => -1]))->default())
        ->toThrow(InvalidSessionConfigException::class);
});
