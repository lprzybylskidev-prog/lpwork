<?php

declare(strict_types=1);

use Tests\support\config\ConfigReaderFactory;

it('reads typed string lists', function (): void {
    $reader = ConfigReaderFactory::create([
        'middleware' => ['web', 'csrf'],
    ]);

    expect($reader->stringList('middleware'))->toBe(['web', 'csrf']);
});

it('rejects invalid string lists', function (): void {
    $reader = ConfigReaderFactory::create([
        'middleware' => ['web', 15],
    ]);

    expect(fn(): array => $reader->stringList('middleware'))
        ->toThrow(\RuntimeException::class, 'invalid: middleware');
});

it('reads typed string maps', function (): void {
    $reader = ConfigReaderFactory::create([
        'headers' => [
            'X-Frame-Options' => 'DENY',
        ],
    ]);

    expect($reader->stringMap('headers'))->toBe([
        'X-Frame-Options' => 'DENY',
    ]);
});

it('reads typed array maps', function (): void {
    $reader = ConfigReaderFactory::create([
        'drivers' => [
            'php' => ['driver' => 'php'],
            'memory' => ['driver' => 'memory'],
        ],
    ]);

    expect($reader->arrayMap('drivers'))->toBe([
        'php' => ['driver' => 'php'],
        'memory' => ['driver' => 'memory'],
    ]);
});
