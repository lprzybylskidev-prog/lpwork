<?php

declare(strict_types=1);

use LPWork\Security\Csrf\CsrfConfig;
use LPWork\Security\Csrf\CsrfInput;
use LPWork\Session\Session;

it('generates hidden CSRF token input and stores the token in the session', function (): void {
    $session = new Session();

    $input = CsrfInput::input($session);
    $token = $session->get('_csrf_token');

    expect($token)->toBeString();

    if (!is_string($token)) {
        return;
    }

    expect($input)->toBe(sprintf('<input type="hidden" name="_token" value="%s">', $token));
});

it('uses configured CSRF input and session keys', function (): void {
    $session = new Session();
    $config = new CsrfConfig(
        enabled: true,
        sessionKey: '_custom_csrf',
        inputKey: 'csrf',
        headerName: 'X-CUSTOM-CSRF',
        rotate: false,
        perForm: false,
    );

    $input = CsrfInput::fromConfig($session, $config);
    $token = $session->get('_custom_csrf');

    expect($token)->toBeString();

    if (!is_string($token)) {
        return;
    }

    expect($input)->toBe(sprintf('<input type="hidden" name="csrf" value="%s">', $token));
});

it('generates per-form CSRF inputs', function (): void {
    $session = new Session();
    $config = new CsrfConfig(
        enabled: true,
        sessionKey: '_custom_csrf',
        inputKey: 'csrf',
        headerName: 'X-CUSTOM-CSRF',
        rotate: false,
        perForm: true,
    );

    $input = CsrfInput::forForm($session, $config, 'profile');
    $tokens = $session->get('_custom_csrf_forms');

    expect($tokens)->toBeArray();

    if (!is_array($tokens)) {
        return;
    }

    $token = $tokens['profile'] ?? null;

    expect($token)->toBeString();

    if (!is_string($token)) {
        return;
    }

    expect($input)->toBe(sprintf(
        '<input type="hidden" name="csrf_form" value="profile"><input type="hidden" name="csrf" value="%s">',
        $token,
    ));
});
