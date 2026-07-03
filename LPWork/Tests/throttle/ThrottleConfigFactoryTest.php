<?php

declare(strict_types=1);

use LPWork\Throttle\Exceptions\InvalidThrottleConfigException;
use LPWork\Throttle\Exceptions\MissingThrottleConfigException;
use LPWork\Throttle\ThrottleConfigFactory;

it('creates throttle config with named policies', function (): void {
    $config = new ThrottleConfigFactory()->create([
        'storage' => 'memory',
        'policies' => [
            'http_web' => [
                'enabled' => true,
                'max_attempts' => 10,
                'decay_seconds' => 60,
            ],
            'http_api' => [
                'enabled' => false,
                'max_attempts' => 20,
                'decay_seconds' => 30,
            ],
        ],
    ]);

    expect($config->storage())->toBe('memory')
        ->and($config->policy('http_web')->enabled())->toBeTrue()
        ->and($config->policy('http_web')->maxAttempts())->toBe(10)
        ->and($config->policy('http_api')->enabled())->toBeFalse()
        ->and($config->policy('http_api')->decaySeconds())->toBe(30);
});

it('rejects missing throttle config values', function (): void {
    expect(fn() => new ThrottleConfigFactory()->create([
        'storage' => 'memory',
    ]))->toThrow(MissingThrottleConfigException::class);
});

it('rejects invalid throttle policies', function (): void {
    expect(fn() => new ThrottleConfigFactory()->create([
        'storage' => 'memory',
        'policies' => [
            'cli' => [
                'enabled' => true,
                'max_attempts' => 0,
                'decay_seconds' => 60,
            ],
        ],
    ]))->toThrow(InvalidThrottleConfigException::class);
});
