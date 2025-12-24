<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

/**
 * Redis connections for Redis-backed components.
 * default_connection: name used when no Redis connection is specified.
 * connections.*.scheme: protocol (tcp/unix).
 * connections.*.host: hostname or socket path (for unix).
 * connections.*.port: port number for tcp connections.
 * connections.*.database: logical database index.
 * connections.*.username: ACL username (if enabled).
 * connections.*.password: password/token (if required).
 * connections.*.prefix: key prefix applied by the client.
 */
return [
    // Default Redis connection name.
    'default_connection' => 'default',
    'connections' => [
        'default' => [
            // Connection scheme (tcp or unix).
            'scheme' => $env->getString('REDIS_SCHEME', 'tcp'),
            // Redis host or socket path (for unix scheme).
            'host' => $env->getString('REDIS_HOST', '127.0.0.1'),
            // Redis port (ignored for unix sockets).
            'port' => $env->getInt('REDIS_PORT', 6379),
            // Logical database index.
            'database' => $env->getInt('REDIS_DB', 0),
            // ACL username if required.
            'username' => $env->getString('REDIS_USERNAME', ''),
            // Password/token if authentication is enabled.
            'password' => $env->getString('REDIS_PASSWORD', ''),
            // Key prefix for namespacing.
            'prefix' => $env->getString('REDIS_PREFIX', ''),
        ],
        // Add more named connections here, e.g. "cache", "session".
    ],
];
