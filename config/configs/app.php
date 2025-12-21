<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

$timezone = \trim($env->getString("APP_TIMEZONE", "UTC"));

if ($timezone === "") {
    $timezone = "UTC";
}

return [
    /**
     * Application identity and base URL settings.
     * name: human-readable identifier shown in logs/metadata.
     * version: semantic or free-form version label of the framework/app.
     * env: runtime mode (dev/prod/test) used by various guards.
     * timezone: default timezone applied globally (PSR-20 clock + Carbon), trimmed and falling back to UTC when empty; accepts any IANA name (aliases allowed) and fails fast on invalid.
     * locale: default language/locale identifier.
     * scheme: base scheme for URL generation (http/https).
     * url: full base URL including host and port.
     */
    // Application name used across logging and diagnostics.
    "name" => $env->getString("APP_NAME", "LPwork"),
    // Application/framework version label.
    "version" => $env->getString("APP_VERSION", "0.0.1"),
    // Runtime environment flag (dev/prod/test).
    "env" => $env->getString("APP_ENV", "dev"),
    // Default timezone applied globally (aliases allowed, empty => UTC).
    "timezone" => $timezone,
    // Default locale/language code.
    "locale" => $env->getString("APP_LOCALE", "en"),
    // Base scheme used for URL building.
    "scheme" => $env->getString("APP_SCHEME", "http"),
    // Base URL including host and port.
    "url" => $env->getString("APP_URL", "http://localhost:8080"),
];
