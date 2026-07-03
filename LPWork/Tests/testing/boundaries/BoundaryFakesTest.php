<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Environment\Environment;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\LogChannel;
use LPWork\Responses\HttpResponse;
use LPWork\Throttle\ThrottleLimiter;
use LPWork\Throttle\ThrottlePolicy;
use PHPUnit\Framework\AssertionFailedError;
use Tests\support\testing\ApplicationTestHarness;
use Tests\support\testing\Cache\TestCacheStore;
use Tests\support\testing\Emitters\CapturingEmitter;
use Tests\support\testing\Filesystem\TestFilesystem;
use Tests\support\testing\Logging\TestLogDriver;
use Tests\support\testing\Security\TestApplicationKeys;
use Tests\support\testing\State\TestFrameworkState;
use Tests\support\testing\Storage\TestStorageDisk;
use Tests\support\testing\Throttle\TestThrottleClock;
use Tests\support\testing\Throttle\TestThrottleStorage;

beforeEach(function (): void {
    TestFrameworkState::reset();
});

afterEach(function (): void {
    TestFrameworkState::reset();
});

afterAll(function (): void {
    ApplicationTestHarness::removeDirectories();
});

it('asserts filesystem side effects inside an isolated test root', function (): void {
    $filesystem = TestFilesystem::create();

    try {
        $filesystem
            ->write('logs/app.log', 'first')
            ->append('logs/app.log', ' second')
            ->assertFileExists('logs/app.log')
            ->assertFileContains('logs/app.log', 'second')
            ->assertFileEquals('logs/app.log', 'first second')
            ->delete('logs/app.log')
            ->assertFileMissing('logs/app.log');
    } finally {
        $filesystem->cleanup();
    }
});

it('asserts storage disk side effects without touching the local filesystem', function (): void {
    TestStorageDisk::create()
        ->put('cache/item.txt', 'first')
        ->append('cache/item.txt', ' second')
        ->assertExists('cache/item.txt')
        ->assertContents('cache/item.txt', 'first second')
        ->clear('cache')
        ->assertMissing('cache/item.txt');
});

it('asserts cache store state through the cache store API', function (): void {
    TestCacheStore::create()
        ->put('feature.enabled', true)
        ->assertHas('feature.enabled', true)
        ->forget('feature.enabled')
        ->assertMissing('feature.enabled')
        ->put('other', 'value')
        ->clear()
        ->assertMissing('other');
});

it('captures and asserts logging records through the log driver contract', function (): void {
    $driver = new TestLogDriver();
    $logger = new LogChannel('testing', $driver);

    $logger->info('Stored {item}', ['item' => 'record']);

    $driver->assertLogged(LogLevel::Info, 'Stored record');

    expect(fn(): TestLogDriver => $driver->assertLogged(LogLevel::Error, 'Stored record'))
        ->toThrow(AssertionFailedError::class);
});

it('controls throttle time and storage attempts through reusable fakes', function (): void {
    $clock = new TestThrottleClock();
    $storage = new TestThrottleStorage();
    $limiter = new ThrottleLimiter($storage, $clock);
    $policy = new ThrottlePolicy(name: 'cli', enabled: true, maxAttempts: 2, decaySeconds: 60);

    expect($limiter->attempt($policy, 'preview')->allowed())->toBeTrue()
        ->and($limiter->attempt($policy, 'preview')->allowed())->toBeTrue()
        ->and($limiter->attempt($policy, 'preview')->allowed())->toBeFalse();

    $storage->assertAttempts('cli:preview', 3);
    $clock->travel(60)->assertNow(1060);

    expect($limiter->attempt($policy, 'preview')->allowed())->toBeTrue();

    $storage->assertAttempts('cli:preview', 1);
});

it('captures emitted responses through the generic emitter contract', function (): void {
    $response = HttpResponse::text('captured');
    $emitter = new CapturingEmitter(exitCode: 204);

    expect($emitter->emit($response))->toBe(204);

    $emitter
        ->assertEmitted()
        ->assertLastResponse($response);
});

it('provides deterministic valid application keys for security tests', function (): void {
    $key = TestApplicationKeys::string('security');

    TestApplicationKeys::assertValid($key);

    expect(TestApplicationKeys::key('security')->bytes())->toBe(str_pad('security', 32, 'security'));
});

it('resets and asserts environment and configuration state', function (): void {
    $harness = ApplicationTestHarness::create()
        ->writeEnv(['APP_ENV' => 'testing'])
        ->writeConfig('app.php', ['env' => 'testing']);

    Environment::init($harness->envPath());
    Config::init($harness->configPath());

    TestFrameworkState::assertEnvironmentValue('APP_ENV', 'testing');
    TestFrameworkState::assertConfigValue('app.env', 'testing');

    TestFrameworkState::reset();

    expect(fn(): string => Environment::getString('APP_ENV'))
        ->toThrow(LPWork\Shared\Exceptions\SingletonInstanceException::class);
});
