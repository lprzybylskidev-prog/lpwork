<?php

declare(strict_types=1);

use LPWork\Security\HmacSigner;
use LPWork\Security\SignedUrl;
use Tests\support\queue\MutableClock;
use Tests\support\testing\Security\TestApplicationKeys;

it('signs and verifies URLs with canonical query ordering', function (): void {
    $signed = new SignedUrl(new HmacSigner(TestApplicationKeys::key()));

    $url = $signed->sign('/download?b=2&a=1');

    expect($url)->toStartWith('/download?b=2&a=1&signature=')
        ->and($signed->verify($url))->toBeTrue()
        ->and($signed->verify(str_replace('b=2&a=1', 'a=1&b=2', $url)))->toBeTrue()
        ->and($signed->verify(str_replace('b=2', 'b=3', $url)))->toBeFalse();
});

it('signs temporary URLs and rejects expired URLs', function (): void {
    $clock = new MutableClock(1000);
    $signed = new SignedUrl(new HmacSigner(TestApplicationKeys::key()), $clock);

    $url = $signed->temporary('/download', 1010);

    expect($url)->toStartWith('/download?expires=1010&signature=')
        ->and($signed->verify($url))->toBeTrue();

    $clock->travel(11);

    expect($signed->verify($url))->toBeFalse();
});

it('rejects missing signatures', function (): void {
    $signed = new SignedUrl(new HmacSigner(TestApplicationKeys::key()));

    expect($signed->verify('/download'))->toBeFalse();
});
