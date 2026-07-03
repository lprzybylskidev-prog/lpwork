<?php

declare(strict_types=1);

use LPWork\Console\CommandDiscovery;
use LPWork\Console\Exceptions\InvalidCommandException;
use LPWork\Container\Container;
use Tests\support\console\DescribedCommand;
use Tests\support\console\NotCommand;

it('discovers command instances through the container', function (): void {
    $container = new Container();

    $commands = new CommandDiscovery($container)->discover([
        DescribedCommand::class,
    ]);

    expect($commands)->toHaveCount(1)
        ->and($commands[0])->toBeInstanceOf(DescribedCommand::class);
});

it('throws when a discovered class is not a command', function (): void {
    $container = new Container();

    expect(fn() => new CommandDiscovery($container)->discover([
        NotCommand::class,
    ]))->toThrow(InvalidCommandException::class, 'must implement the Command contract');
});
