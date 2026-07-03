<?php

declare(strict_types=1);

use LPWork\Console\CommandRegistry;
use LPWork\Console\Exceptions\CommandNotFoundException;
use LPWork\Console\Exceptions\DuplicateCommandException;
use Tests\support\console\TestCommand;

it('registers and returns commands by name', function (): void {
    $registry = new CommandRegistry();
    $command = new TestCommand('test');

    $registry->add($command);

    expect($registry->has('test'))->toBeTrue()
        ->and($registry->get('test'))->toBe($command)
        ->and($registry->all())->toBe(['test' => $command]);
});

it('returns commands sorted by name', function (): void {
    $registry = new CommandRegistry();
    $first = new TestCommand('alpha');
    $second = new TestCommand('zeta');

    $registry->add($second);
    $registry->add($first);

    expect(array_keys($registry->all()))->toBe(['alpha', 'zeta']);
});

it('throws when command is missing', function (): void {
    $registry = new CommandRegistry();

    expect(fn() => $registry->get('missing'))
        ->toThrow(CommandNotFoundException::class);
});

it('throws when command is already registered', function (): void {
    $registry = new CommandRegistry();

    $registry->add(new TestCommand('test'));

    expect(fn() => $registry->add(new TestCommand('test')))
        ->toThrow(DuplicateCommandException::class);
});
