<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

$root = $env->getString(
    "FILESYSTEM_LOCAL_ROOT",
    \dirname(__DIR__, 2) . "/storage",
);

return [
    "default_disk" => "local",
    "disks" => [
        "local" => [
            "driver" => "local",
            "root" => $root,
        ],
        // Additional disks (e.g., "logs", "cache") can be added here.
    ],
];
