<?php

declare(strict_types=1);

use LPWork\Logging\Enums\LogLevel;
use LPWork\Requests\ConsoleRequest;
use LPWork\Throttle\CliThrottle;
use LPWork\Throttle\Storage\InMemoryThrottleStorage;
use LPWork\Throttle\ThrottleLimiter;
use Tests\support\console\OutputStreams;
use Tests\support\events\EventDispatcherFactory;
use Tests\support\testing\Logging\TestLogDriver;
use Tests\support\throttle\MutableThrottleClock;
use Tests\support\throttle\ThrottleConfigBuilder;

it('returns no response while CLI attempts are allowed', function (): void {
    $throttle = new CliThrottle(
        ThrottleConfigBuilder::config(cli: true),
        new ThrottleLimiter(new InMemoryThrottleStorage(), new MutableThrottleClock()),
    );

    expect($throttle->response(ConsoleRequest::fromArgv(['lpwork', 'preview'])))->toBeNull();
});

it('returns an error response when CLI attempts are throttled', function (): void {
    $throttle = new CliThrottle(
        ThrottleConfigBuilder::config(cli: true, maxAttempts: 1),
        new ThrottleLimiter(new InMemoryThrottleStorage(), new MutableThrottleClock()),
    );
    $request = ConsoleRequest::fromArgv(['lpwork', 'preview']);

    $throttle->response($request);
    $response = $throttle->response($request);
    $streams = OutputStreams::create();

    expect($response)->not->toBeNull();

    if ($response === null) {
        return;
    }

    expect($response->send(new LPWork\Console\Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(1)
        ->and($streams->stderr())->toBe("Too many CLI attempts. Retry after 60 seconds.\n");
});

it('logs throttled CLI commands', function (): void {
    $driver = new TestLogDriver();
    $throttle = new CliThrottle(
        ThrottleConfigBuilder::config(cli: true, maxAttempts: 1),
        new ThrottleLimiter(new InMemoryThrottleStorage(), new MutableThrottleClock()),
        EventDispatcherFactory::withLogger($driver),
    );
    $request = ConsoleRequest::fromArgv(['lpwork', 'preview']);

    $throttle->response($request);
    $throttle->response($request);

    $record = $driver->records()[0];

    expect($record->level)->toBe(LogLevel::Warning)
        ->and($record->message)->toBe('CLI command throttled.')
        ->and($record->context['command'])->toBe('preview')
        ->and($record->context['retry_after'])->toBe(60);
});
