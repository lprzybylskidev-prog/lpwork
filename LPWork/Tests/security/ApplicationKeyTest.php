<?php

declare(strict_types=1);

use LPWork\Security\ApplicationKey;
use LPWork\Security\Exceptions\InvalidApplicationKeyException;

it('creates application keys from base64 encoded secret material', function (): void {
    $key = ApplicationKey::fromString('base64:' . base64_encode(str_repeat('a', 32)));

    expect($key->bytes())->toBe(str_repeat('a', 32));
});

it('creates application keys from raw secret material', function (): void {
    $secret = str_repeat('b', 32);

    expect(ApplicationKey::fromString($secret)->bytes())->toBe($secret);
});

it('rejects empty application keys', function (): void {
    expect(fn(): ApplicationKey => ApplicationKey::fromString(''))->toThrow(InvalidApplicationKeyException::class);
});

it('rejects malformed base64 application keys', function (): void {
    expect(fn(): ApplicationKey => ApplicationKey::fromString('base64:not base64'))->toThrow(InvalidApplicationKeyException::class);
});

it('rejects short application keys', function (): void {
    expect(fn(): ApplicationKey => ApplicationKey::fromString('short'))->toThrow(InvalidApplicationKeyException::class)
        ->and(fn(): ApplicationKey => ApplicationKey::fromString('base64:' . base64_encode('short')))->toThrow(InvalidApplicationKeyException::class);
});
