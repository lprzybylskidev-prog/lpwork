<?php

declare(strict_types=1);

use LPWork\Console\InputParser;

it('parses argv into arguments and options', function (): void {
    $parsed = new InputParser()->parse(['lpwork', 'command', 'name', '--path=tests', '--force', '-vv']);

    expect($parsed)->toBe([
        'arguments' => ['name'],
        'options' => [
            'path' => 'tests',
            'force' => true,
            'v' => 2,
        ],
    ]);
});

it('parses repeated options as multi-value options', function (): void {
    $parsed = new InputParser()->parse(['lpwork', 'command', '--tag=one', '--tag', 'two', '-p=App', '-p=tests']);

    expect($parsed['options'])->toBe([
        'tag' => ['one', 'two'],
        'p' => ['App', 'tests'],
    ]);
});
