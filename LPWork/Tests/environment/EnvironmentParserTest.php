<?php

declare(strict_types=1);

use LPWork\Environment\EnvironmentParser;
use LPWork\Environment\Exceptions\InvalidLineStructureException;

it('parses environment content', function (): void {
    $parser = new EnvironmentParser();

    expect($parser->parse(
        <<<'ENV'
            APP_NAME=LPWork
            EMPTY=
            QUOTED="hello world"
            SINGLE='it\'s ok'
            # ignored
            ENV,
        '.env',
    ))->toBe([
        'APP_NAME' => 'LPWork',
        'EMPTY' => '',
        'QUOTED' => 'hello world',
        'SINGLE' => "it's ok",
    ]);
});

it('rejects invalid environment lines with real line numbers', function (): void {
    $parser = new EnvironmentParser();

    expect(fn(): array => $parser->parse("\nVALID=value\nINVALID VALUE\n", '.env'))
        ->toThrow(InvalidLineStructureException::class, 'Invalid .env structure on line 3: INVALID VALUE');
});
