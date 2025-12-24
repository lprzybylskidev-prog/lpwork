<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

/**
 * HTTP runtime settings.
 */
return [
    // Enable body parsing middleware (true/false).
    'enabled' => $env->getBool('HTTP_BODY_PARSING_ENABLED', true),
    // Maximum accepted body size in bytes (0 = unlimited).
    'max_body_size' => $env->getInt('HTTP_BODY_MAX_SIZE', 1048576),
    // Treat invalid JSON as 400 Bad Request (true) or skip parsing (false).
    'reject_invalid_json' => $env->getBool('HTTP_BODY_REJECT_INVALID_JSON', true),
    // Allowed content types for parsing.
    'allowed_types' => [
        // JSON media types.
        'application/json',
        'application/*+json',
        // Form-URL-encoded.
        'application/x-www-form-urlencoded',
    ],
    'json' => [
        // Maximum JSON depth.
        'max_depth' => $env->getInt('HTTP_BODY_JSON_MAX_DEPTH', 512),
        // Decode JSON objects as associative arrays (true) or stdClass (false).
        'assoc' => $env->getBool('HTTP_BODY_JSON_ASSOC', false),
    ],
];
