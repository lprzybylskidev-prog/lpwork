<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

return [
    /**
     * HTTP runtime settings.
     * body_parsing: options for request body parsing middleware.
     * cors: Cross-Origin Resource Sharing policy.
     */
    'body_parsing' => [
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
    ],
    'cors' => [
        // Enable CORS middleware (true/false).
        'enabled' => $env->getBool('HTTP_CORS_ENABLED', false),
        // Allowed origins (use "*" to allow all).
        'allow_origin' => [$env->getString('HTTP_CORS_ALLOW_ORIGIN', '*')],
        // Allowed HTTP methods.
        'allow_methods' => [
            $env->getString('HTTP_CORS_ALLOW_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS'),
        ],
        // Allowed headers sent by the client.
        'allow_headers' => [
            $env->getString('HTTP_CORS_ALLOW_HEADERS', 'Content-Type,Authorization'),
        ],
        // Headers exposed to the client.
        'expose_headers' => [$env->getString('HTTP_CORS_EXPOSE_HEADERS', '')],
        // Whether to allow credentials.
        'allow_credentials' => $env->getBool('HTTP_CORS_ALLOW_CREDENTIALS', false),
        // Max age for preflight responses (seconds).
        'max_age' => $env->getInt('HTTP_CORS_MAX_AGE', 600),
    ],
];
