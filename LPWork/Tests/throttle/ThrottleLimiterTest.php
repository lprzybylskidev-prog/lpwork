<?php

declare(strict_types=1);

use LPWork\Throttle\Storage\InMemoryThrottleStorage;
use LPWork\Throttle\ThrottleLimiter;
use LPWork\Throttle\ThrottlePolicy;
use Tests\support\throttle\MutableThrottleClock;

it('allows disabled throttle policies without touching limits', function (): void {
    $limiter = new ThrottleLimiter(new InMemoryThrottleStorage(), new MutableThrottleClock());

    $result = $limiter->attempt(new ThrottlePolicy('web', false, 1, 60), 'client');

    expect($result->allowed())->toBeTrue()
        ->and($result->maxAttempts())->toBe(0)
        ->and($result->remaining())->toBe(0);
});

it('denies attempts after a policy limit until the window expires', function (): void {
    $clock = new MutableThrottleClock();
    $limiter = new ThrottleLimiter(new InMemoryThrottleStorage(), $clock);
    $policy = new ThrottlePolicy('api', true, 2, 60);

    expect($limiter->attempt($policy, 'client')->allowed())->toBeTrue()
        ->and($limiter->attempt($policy, 'client')->allowed())->toBeTrue();

    $denied = $limiter->attempt($policy, 'client');

    expect($denied->allowed())->toBeFalse()
        ->and($denied->retryAfter())->toBe(60)
        ->and($denied->remaining())->toBe(0);

    $clock->advance(60);

    expect($limiter->attempt($policy, 'client')->allowed())->toBeTrue();
});

it('keeps policies and keys isolated', function (): void {
    $limiter = new ThrottleLimiter(new InMemoryThrottleStorage(), new MutableThrottleClock());
    $web = new ThrottlePolicy('web', true, 1, 60);
    $api = new ThrottlePolicy('api', true, 1, 60);

    expect($limiter->attempt($web, 'client')->allowed())->toBeTrue()
        ->and($limiter->attempt($web, 'client')->allowed())->toBeFalse()
        ->and($limiter->attempt($api, 'client')->allowed())->toBeTrue()
        ->and($limiter->attempt($web, 'other-client')->allowed())->toBeTrue();
});
