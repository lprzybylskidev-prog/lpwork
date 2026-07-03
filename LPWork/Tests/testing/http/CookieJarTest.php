<?php

declare(strict_types=1);

use LPWork\Http\Cookie;
use LPWork\Responses\HttpResponse;
use Tests\support\testing\Http\CookieJar;

it('captures response cookies and sends matching request cookies', function (): void {
    $jar = new CookieJar();

    $jar->capture(HttpResponse::text('ok')->withCookie(new Cookie('theme', 'dark', path: '/settings')));

    expect($jar->requestCookies('/settings/profile'))->toBe(['theme' => 'dark'])
        ->and($jar->requestCookies('/profile'))->toBe([]);
});

it('forgets expired response cookies', function (): void {
    $jar = new CookieJar();
    $jar->put('theme', 'dark', '/settings');

    $jar->capture(HttpResponse::text('ok')->withoutCookie('theme', '/settings'));

    expect($jar->has('theme', '/settings'))->toBeFalse()
        ->and($jar->requestCookies('/settings'))->toBe([]);
});

it('matches domain and secure cookie rules without touching globals', function (): void {
    $_COOKIE['session'] = 'global';

    try {
        $jar = new CookieJar();
        $jar->put('session', 'abc', domain: 'example.test', secure: true);

        expect($jar->requestCookies('http://example.test/dashboard'))->toBe([])
            ->and($jar->requestCookies('https://example.test/dashboard', secure: true))->toBe(['session' => 'abc'])
            ->and($jar->requestCookies('https://admin.example.test/dashboard', secure: true))->toBe(['session' => 'abc'])
            ->and($_COOKIE['session'])->toBe('global');
    } finally {
        unset($_COOKIE['session']);
    }
});
