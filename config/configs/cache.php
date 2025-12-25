<?php
declare(strict_types=1);

/** @var \LPwork\Environment\Env $env */

/**
 * Cache configuration (PSR-6/16).
 * default_pool: name of the pool used when none specified.
 * pools.*: named cache pools with driver-specific settings.
 * Supported drivers: array, filesystem, redis, pdo.
 * routing: cache settings for FastRoute dispatcher (optional).
 * config_cache: cache settings for configuration repository (optional).
 */
return [
    // Name of the default cache pool.
    'default_pool' => $env->getString('CACHE_DEFAULT_POOL', 'array'),
    'pools' => [
        'array' => [
            // In-memory cache (non-persistent). Good for dev/test.
            'driver' => 'array',
            // Namespace prefix for keys.
            'namespace' => $env->getString('CACHE_ARRAY_NAMESPACE', ''),
            // Default TTL in seconds (null = no default expiry).
            'default_ttl' => $env->getInt('CACHE_ARRAY_TTL', 0) ?: null,
        ],
        'filesystem' => [
            // Filesystem cache.
            'driver' => 'filesystem',
            // Namespace prefix for keys.
            'namespace' => $env->getString('CACHE_FS_NAMESPACE', ''),
            // Default TTL in seconds.
            'default_ttl' => $env->getInt('CACHE_FS_TTL', 0) ?: null,
            // Directory for cache files.
            'path' => $env->getString('CACHE_FS_PATH', \dirname(__DIR__, 2) . '/storage/cache'),
        ],
        'redis' => [
            // Redis cache via RedisConnectionManager (Predis).
            'driver' => 'redis',
            // Redis connection name.
            'connection' => $env->getString('CACHE_REDIS_CONNECTION', 'default'),
            // Namespace prefix for keys.
            'namespace' => $env->getString('CACHE_REDIS_NAMESPACE', ''),
            // Default TTL in seconds.
            'default_ttl' => $env->getInt('CACHE_REDIS_TTL', 0) ?: null,
        ],
        'pdo' => [
            // PDO cache via DatabaseConnectionManager.
            'driver' => 'pdo',
            // Database connection name.
            'connection' => $env->getString('CACHE_PDO_CONNECTION', 'default'),
            // Namespace prefix for keys.
            'namespace' => $env->getString('CACHE_PDO_NAMESPACE', ''),
            // Default TTL in seconds.
            'default_ttl' => $env->getInt('CACHE_PDO_TTL', 0) ?: null,
            // Table name for cache items (will be created by adapter if possible).
            'table' => $env->getString('CACHE_PDO_TABLE', 'cache_items'),
        ],
    ],
    'routing' => [
        // Enable FastRoute dispatcher cache (true/false).
        'enabled' => $env->getBool('ROUTE_CACHE_ENABLED', false),
        // Cache pool name used for routing cache.
        'pool' => $env->getString('ROUTE_CACHE_POOL', 'filesystem'),
        // Cache key for dispatcher.
        'key' => $env->getString('ROUTE_CACHE_KEY', 'routes:dispatcher'),
    ],
    'config_cache' => [
        // Enable configuration cache (true/false).
        'enabled' => $env->getBool('CONFIG_CACHE_ENABLED', false),
        // Cache pool name used for configuration cache.
        'pool' => $env->getString('CONFIG_CACHE_POOL', 'filesystem'),
        // Cache key for configuration repository payload.
        'key' => $env->getString('CONFIG_CACHE_KEY', 'config:repository'),
    ],
    'translations' => [
        // Enable translation catalogue cache (true/false).
        'enabled' => $env->getBool('TRANSLATION_CACHE_ENABLED', true),
        // Cache pool name used for translation catalogues (optional).
        'pool' => $env->getString('TRANSLATION_CACHE_POOL', 'filesystem'),
        // Cache key prefix for translation catalogue.
        'prefix' => $env->getString('TRANSLATION_CACHE_PREFIX', 'translations:'),
    ],
];
