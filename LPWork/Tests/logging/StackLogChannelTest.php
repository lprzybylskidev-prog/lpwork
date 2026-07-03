<?php

declare(strict_types=1);

use LPWork\Logging\LogChannel;
use LPWork\Logging\StackLogChannel;
use Tests\support\testing\Logging\TestLogDriver;

it('forwards every log level to each configured channel', function (): void {
    $first = new TestLogDriver();
    $second = new TestLogDriver();

    $stack = new StackLogChannel([
        new LogChannel('first', $first),
        new LogChannel('second', $second),
    ]);

    $stack->debug('Debug {id}', ['id' => 1]);
    $stack->info('Info {id}', ['id' => 2]);
    $stack->notice('Notice {id}', ['id' => 3]);
    $stack->warning('Warning {id}', ['id' => 4]);
    $stack->error('Error {id}', ['id' => 5]);
    $stack->critical('Critical {id}', ['id' => 6]);

    foreach ([$first, $second] as $driver) {
        expect(array_map(
            static fn(LPWork\Logging\LogRecord $record): string => $record->message,
            $driver->records(),
        ))->toBe([
            'Debug 1',
            'Info 2',
            'Notice 3',
            'Warning 4',
            'Error 5',
            'Critical 6',
        ]);
    }
});

it('allows an empty stack channel to absorb log records without side effects', function (): void {
    $stack = new StackLogChannel([]);

    $stack->info('Ignored');

    expect(true)->toBeTrue();
});
