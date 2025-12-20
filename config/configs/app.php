<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

return [
    'name' => $env->getString('APP_NAME', 'LPwork'),
    'version' => $env->getString('APP_VERSION', '0.0.1'),
    'env' => $env->getString('APP_ENV', 'dev'),
];
