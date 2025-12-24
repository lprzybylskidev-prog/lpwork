<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

/**
 * WebSocket server configuration (Ratchet).
 * default_server: name of default server.
 * servers: named server definitions (host/port/origins/enabled).
 */
return [
    // Name of the default WebSocket server.
    'default_server' => $env->getString('WEBSOCKET_DEFAULT', 'default'),
    // Named WebSocket servers.
    'servers' => [
        'default' => [
            // Enable or disable this server.
            'enabled' => $env->getBool('WEBSOCKET_DEFAULT_ENABLED', true),
            // Bind host.
            'host' => $env->getString('WEBSOCKET_DEFAULT_HOST', '0.0.0.0'),
            // Bind port.
            'port' => $env->getInt('WEBSOCKET_DEFAULT_PORT', 8081),
            // Allowed origins (comma-separated env); "*" or empty allows all.
            'allowed_origins' => \array_filter(
                \array_map(
                    'trim',
                    \explode(',', $env->getString('WEBSOCKET_DEFAULT_ORIGINS', '*')),
                ),
                static fn(string $value): bool => $value !== '',
            ),
        ],
    ],
];
