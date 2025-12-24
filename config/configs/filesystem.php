<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

$root = $env->getString('FILESYSTEM_LOCAL_ROOT', \dirname(__DIR__, 2) . '/storage');

/**
 * Filesystem disks configuration.
 * default_disk: disk name used when none is specified.
 * disks.local.driver: filesystem driver identifier (only "local" supported).
 * disks.local.root: absolute/root path for local storage.
 */
return [
    // Default filesystem disk name.
    'default_disk' => 'local',
    'disks' => [
        'local' => [
            // Driver type for this disk.
            'driver' => 'local',
            // Root directory for the local disk.
            'root' => $root,
        ],
        // Additional disks (e.g., "logs", "cache") can be added here.
    ],
];
