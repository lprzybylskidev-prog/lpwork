<?php
declare(strict_types=1);

/** @var \LPwork\Environment\Env $env */

$timezone = \trim($env->getString('APP_TIMEZONE', 'UTC'));

if ($timezone === '') {
    $timezone = 'UTC';
}

/**
 * Application identity and base URL settings.
 * name: human-readable identifier shown in logs/metadata.
 * version: semantic or free-form version label of the framework/app.
 * env: runtime mode (dev/prod/test) used by various guards.
 * timezone: default timezone applied globally (PSR-20 clock + Carbon), trimmed and falling back to UTC when empty; accepts any IANA name (aliases allowed) and fails fast on invalid.
 * locale: default language/locale identifier.
 * scheme: base scheme for URL generation (http/https).
 * http_client: default PSR-18 client options used by the framework/app.
 */
return [
    // Application name used across logging and diagnostics.
    'name' => $env->getString('APP_NAME', 'LPwork'),
    // Application/framework version label.
    'version' => $env->getString('APP_VERSION', '0.0.1'),
    // Runtime environment flag (dev/prod/test).
    'env' => $env->getString('APP_ENV', 'dev'),
    // Default timezone applied globally (aliases allowed, empty => UTC).
    'timezone' => $timezone,
    // Default locale/language code.
    'locale' => $env->getString('APP_LOCALE', 'en'),
    // Base scheme used for URL building.
    'scheme' => $env->getString('APP_SCHEME', 'http'),
    // Default HTTP client options (PSR-18 via Symfony HttpClient).
    'http_client' => [
        // Base URI for outgoing requests; empty to disable.
        'base_uri' => $env->getString('HTTP_CLIENT_BASE_URI', ''),
        // Timeout in seconds for HTTP requests.
        'timeout' => $env->getFloat('HTTP_CLIENT_TIMEOUT', 30.0),
        // Maximum number of redirects to follow.
        'max_redirects' => $env->getInt('HTTP_CLIENT_MAX_REDIRECTS', 10),
        // TLS verification toggle (peer/host).
        'verify' => $env->getBool('HTTP_CLIENT_VERIFY', true),
        // Default headers appended to each request.
        'headers' => [],
    ],
];
