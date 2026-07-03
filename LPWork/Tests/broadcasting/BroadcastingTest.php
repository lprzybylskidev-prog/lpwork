<?php

declare(strict_types=1);

use LPWork\Broadcasting\BroadcastChannelRegistry;
use LPWork\Broadcasting\BroadcastDriverFactory;
use LPWork\Broadcasting\BroadcastManager;
use LPWork\Broadcasting\BroadcastMessage;
use LPWork\Broadcasting\Drivers\InMemoryBroadcaster;
use LPWork\Broadcasting\Exceptions\DuplicateBroadcastChannelException;
use LPWork\Broadcasting\Exceptions\InvalidBroadcastingDriverException;
use LPWork\Broadcasting\Exceptions\InvalidBroadcastMessageException;

it('registers broadcast channels with authorization metadata', function (): void {
    $registry = new BroadcastChannelRegistry();

    $registry->public('public');
    $registry->private('users.42', static fn(mixed $id): bool => $id === '42');

    expect($registry->names())->toBe(['public', 'users.42'])
        ->and($registry->get('public')->allows('anything'))->toBeTrue()
        ->and($registry->get('users.42')->isPrivate())->toBeTrue()
        ->and($registry->get('users.42')->allows('42'))->toBeTrue()
        ->and($registry->get('users.42')->allows('7'))->toBeFalse()
        ->and(fn() => $registry->public('public'))->toThrow(DuplicateBroadcastChannelException::class);
});

it('broadcasts messages through configured drivers', function (): void {
    $manager = new BroadcastManager(
        config: [
            'default' => 'sync',
            'connections' => [
                'sync' => ['driver' => 'sync'],
            ],
        ],
        driverFactory: new BroadcastDriverFactory(),
    );

    $result = $manager->broadcast(new BroadcastMessage(['public'], 'order.created', ['id' => 1]));
    $driver = $manager->broadcaster('sync');

    if (!$driver instanceof InMemoryBroadcaster) {
        throw new RuntimeException('Expected in-memory broadcaster.');
    }

    expect($result->driver)->toBe('sync')
        ->and($result->event)->toBe('order.created')
        ->and($driver->messages())->toHaveCount(1)
        ->and($manager->broadcasterNames())->toBe(['sync']);
});

it('validates broadcast messages and drivers', function (): void {
    $factory = new BroadcastDriverFactory();

    expect(fn() => new BroadcastMessage([], 'event'))->toThrow(InvalidBroadcastMessageException::class)
        ->and(fn() => new BroadcastMessage(['public'], ''))->toThrow(InvalidBroadcastMessageException::class)
        ->and(fn() => $factory->create('bad', ['driver' => 'missing'], 'connections.bad'))
        ->toThrow(InvalidBroadcastingDriverException::class);
});
