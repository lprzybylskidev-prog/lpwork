<?php

declare(strict_types=1);

use LPWork\Session\Session;

it('stores and reads session values', function (): void {
    $session = new Session(['user_id' => 15]);

    $session->put('theme', 'dark');

    expect($session->has('user_id'))->toBeTrue()
        ->and($session->get('user_id'))->toBe(15)
        ->and($session->get('theme'))->toBe('dark')
        ->and($session->get('missing', 'fallback'))->toBe('fallback')
        ->and($session->all())->toHaveKeys(['user_id', 'theme', '_flash']);
});

it('forgets pulls and flushes session values', function (): void {
    $session = new Session([
        'token' => 'abc',
        'message' => 'Saved',
    ]);

    expect($session->pull('token'))->toBe('abc')
        ->and($session->has('token'))->toBeFalse();

    $session->forget('message');

    expect($session->has('message'))->toBeFalse();

    $session->put('name', 'LPWork');
    $session->flush();

    expect($session->all())->toBe([
        '_flash' => [
            'new' => [],
            'old' => [],
        ],
    ]);
});

it('ages flash data between requests', function (): void {
    $session = new Session();

    $session->flash('status', 'Saved');

    expect($session->get('status'))->toBe('Saved');

    $session->ageFlashData();

    expect($session->get('status'))->toBe('Saved');

    $session->ageFlashData();

    expect($session->has('status'))->toBeFalse();
});

it('keeps selected flash data for another request', function (): void {
    $session = new Session();

    $session->flash('status', 'Saved');
    $session->flash('notice', 'Profile updated');
    $session->ageFlashData();

    $session->keep('status');
    $session->ageFlashData();

    expect($session->get('status'))->toBe('Saved')
        ->and($session->has('notice'))->toBeFalse();
});

it('reflashes all old flash data', function (): void {
    $session = new Session();

    $session->flash('status', 'Saved');
    $session->flash('notice', 'Profile updated');
    $session->ageFlashData();

    $session->reflash();
    $session->ageFlashData();

    expect($session->get('status'))->toBe('Saved')
        ->and($session->get('notice'))->toBe('Profile updated');
});

it('tracks regeneration and invalidation requests explicitly', function (): void {
    $session = new Session(['user_id' => 15]);

    $session->regenerate();

    expect($session->regenerationRequested())->toBeTrue()
        ->and($session->invalidationRequested())->toBeFalse();

    $session->clearLifecycleRequests();

    expect($session->regenerationRequested())->toBeFalse();

    $session->put('theme', 'dark');
    $session->invalidate();

    expect($session->regenerationRequested())->toBeTrue()
        ->and($session->invalidationRequested())->toBeTrue()
        ->and($session->has('user_id'))->toBeFalse()
        ->and($session->all())->toBe([
            '_flash' => [
                'new' => [],
                'old' => [],
            ],
        ]);
});

it('flashes old input and errors with dot notation helpers', function (): void {
    $session = new Session();

    $session->flashInput([
        'title' => 'Draft',
        'password' => 'secret',
        'author' => [
            'name' => 'Ada',
            'email' => 'ada@example.com',
        ],
    ], ['password', 'author.email']);
    $session->flashErrors([
        'author' => [
            'email' => ['Email is invalid.'],
        ],
    ]);

    expect($session->old('title'))->toBe('Draft')
        ->and($session->old('password', 'missing'))->toBe('missing')
        ->and($session->old('author.name'))->toBe('Ada')
        ->and($session->old('author.email', 'missing'))->toBe('missing')
        ->and($session->error('author.email'))->toBe(['Email is invalid.']);
});
