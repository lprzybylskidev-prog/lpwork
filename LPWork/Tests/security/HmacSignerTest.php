<?php

declare(strict_types=1);

use LPWork\Security\ApplicationKey;
use LPWork\Security\Contracts\Signer;
use LPWork\Security\HmacSigner;

it('signs values using the application key', function (): void {
    $signer = new HmacSigner(ApplicationKey::fromString(str_repeat('s', 32)));

    $signature = $signer->sign('payload');

    expect($signer)->toBeInstanceOf(Signer::class)
        ->and($signature)->toBe(hash_hmac('sha256', 'payload', str_repeat('s', 32)));
});

it('verifies signatures without accepting tampered values', function (): void {
    $signer = new HmacSigner(ApplicationKey::fromString(str_repeat('s', 32)));
    $signature = $signer->sign('payload');

    expect($signer->verify('payload', $signature))->toBeTrue()
        ->and($signer->verify('tampered', $signature))->toBeFalse()
        ->and($signer->verify('payload', str_repeat('0', 64)))->toBeFalse();
});
