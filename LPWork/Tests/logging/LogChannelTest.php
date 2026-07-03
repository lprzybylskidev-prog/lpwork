<?php

declare(strict_types=1);

use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\LogChannel;
use LPWork\Logging\LogRecord;
use LPWork\Time\Contracts\Clock;
use Tests\support\logging\InMemoryLogDriver;

it('creates log records for the configured channel', function (): void {
    $driver = new InMemoryLogDriver();

    $channel = new LogChannel('app', $driver);

    $channel->warning('Something happened', ['request_id' => 'abc']);

    expect($driver->records)->toHaveCount(1);

    $record = $driver->records[0];

    expect($record->channel)->toBe('app')
        ->and($record->level)->toBe(LogLevel::Warning)
        ->and($record->message)->toBe('Something happened')
        ->and($record->context)->toBe(['request_id' => 'abc']);
});

it('maps helper methods to log levels', function (): void {
    $driver = new InMemoryLogDriver();

    $channel = new LogChannel('app', $driver);

    $channel->debug('debug');
    $channel->info('info');
    $channel->notice('notice');
    $channel->warning('warning');
    $channel->error('error');
    $channel->critical('critical');

    expect(array_map(
        static fn(LogRecord $record): LogLevel => $record->level,
        $driver->records,
    ))->toBe([
        LogLevel::Debug,
        LogLevel::Info,
        LogLevel::Notice,
        LogLevel::Warning,
        LogLevel::Error,
        LogLevel::Critical,
    ]);
});

it('filters records below the configured minimum level', function (): void {
    $driver = new InMemoryLogDriver();
    $channel = new LogChannel('app', $driver, LogLevel::Warning);

    $channel->debug('debug');
    $channel->info('info');
    $channel->warning('warning');

    expect($driver->records)->toHaveCount(1)
        ->and($driver->records[0]->level)->toBe(LogLevel::Warning);
});

it('interpolates scalar context values into log messages', function (): void {
    $driver = new InMemoryLogDriver();
    $channel = new LogChannel('app', $driver);

    $channel->info('User {id} failed {action}', [
        'id' => 15,
        'action' => 'login',
        'metadata' => ['ignored'],
    ]);

    expect($driver->records[0]->message)->toBe('User 15 failed login')
        ->and($driver->records[0]->context)->toBe([
            'id' => 15,
            'action' => 'login',
            'metadata' => ['ignored'],
        ]);
});

it('creates log records with the injected clock', function (): void {
    $driver = new InMemoryLogDriver();
    $clock = new class implements Clock {
        public function now(): DateTimeImmutable
        {
            return new DateTimeImmutable('2026-06-23 12:00:00', new DateTimeZone('Europe/Warsaw'));
        }
    };

    $channel = new LogChannel('app', $driver, clock: $clock);

    $channel->info('Timed message');

    expect($driver->records[0]->datetime->format('Y-m-d H:i:s e'))
        ->toBe('2026-06-23 12:00:00 Europe/Warsaw');
});
