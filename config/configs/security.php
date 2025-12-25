<?php
declare(strict_types=1);

/** @var \LPwork\Environment\Env $env */

/**
 * Security configuration: CSRF protection and security headers.
 */
return [
    'csrf' => [
        // Enable CSRF verification (true/false).
        'enabled' => $env->getBool('SECURITY_CSRF_ENABLED', true),
        // Token ID/namespace.
        'token_id' => $env->getString('SECURITY_CSRF_TOKEN_ID', 'default'),
        // Header name checked for the token.
        'header' => $env->getString('SECURITY_CSRF_HEADER', 'X-CSRF-Token'),
        // Request parameter name checked for the token (body/query).
        'parameter' => $env->getString('SECURITY_CSRF_PARAMETER', '_csrf'),
        // HTTP methods that require CSRF verification.
        'methods' => \array_map(
            'trim',
            \explode(',', $env->getString('SECURITY_CSRF_METHODS', 'POST,PUT,PATCH,DELETE')),
        ),
        // Paths excluded from CSRF verification (prefix match).
        'exclude_paths' => \array_filter(
            \array_map('trim', \explode(',', $env->getString('SECURITY_CSRF_EXCLUDE_PATHS', ''))),
            static fn(string $p): bool => $p !== '',
        ),
    ],
    'headers' => [
        // Enable adding security headers.
        'enabled' => $env->getBool('SECURITY_HEADERS_ENABLED', true),
        // X-Frame-Options value (e.g., SAMEORIGIN, DENY).
        'frame_options' => $env->getString('SECURITY_FRAME_OPTIONS', 'SAMEORIGIN'),
        // Referrer-Policy header value.
        'referrer_policy' => $env->getString('SECURITY_REFERRER_POLICY', 'no-referrer'),
        // Permissions-Policy header value (empty to skip).
        'permissions_policy' => $env->getString('SECURITY_PERMISSIONS_POLICY', ''),
        // Add X-Content-Type-Options: nosniff.
        'content_type_options' => $env->getBool('SECURITY_CONTENT_TYPE_OPTIONS', true),
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
