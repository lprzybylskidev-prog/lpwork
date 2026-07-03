<?php

declare(strict_types=1);

use LPWork\Console\ConsoleTable;
use LPWork\Console\ConsoleTableRenderer;
use LPWork\Console\Exceptions\InvalidConsoleTableException;
use LPWork\Console\Output;
use Tests\support\console\OutputStreams;

it('renders rows as an ascii table', function (): void {
    $streams = OutputStreams::create();

    new ConsoleTableRenderer()->render(ConsoleTable::make(
        ['Name', 'Status'],
        [
            ['config:show', 'ready'],
            ['route:list', 'ready'],
        ],
    ), new Output($streams->stdout, $streams->stderr, decorated: false));

    expect($streams->stdout())->toBe(
        "+-------------+--------+\n"
        . "| Name        | Status |\n"
        . "+-------------+--------+\n"
        . "| config:show | ready  |\n"
        . "| route:list  | ready  |\n"
        . "+-------------+--------+\n",
    )->and($streams->stderr())->toBe('');
});

it('pads decorated cells by visible length', function (): void {
    $streams = OutputStreams::create();
    $output = new Output($streams->stdout, $streams->stderr);

    new ConsoleTableRenderer()->render(ConsoleTable::make(
        ['Name', 'Status'],
        [
            ['nginx', $output->format('ok', \LPWork\Console\Enums\ConsoleColor::Green)],
            ['mailpit', $output->format('failed', \LPWork\Console\Enums\ConsoleColor::Red)],
        ],
    ), $output);

    expect($streams->stdout())->toContain("| nginx   | \033[32mok\033[0m     |\n")
        ->and($streams->stdout())->toContain("| mailpit | \033[31mfailed\033[0m |\n")
        ->and($streams->stderr())->toBe('');
});

it('requires headers', function (): void {
    expect(fn() => ConsoleTable::make([], []))
        ->toThrow(InvalidConsoleTableException::class, 'Console table must define at least one header.');
});

it('requires each row to match the header column count', function (): void {
    expect(fn() => ConsoleTable::make(['Name', 'Status'], [['config:show']]))
        ->toThrow(InvalidConsoleTableException::class, 'Console table row 0 has 1 columns, expected 2.');
});
