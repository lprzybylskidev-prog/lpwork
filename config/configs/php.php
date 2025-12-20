<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

return [
    'error_log' => $env->getString('PHP_ERROR_LOG', ''),
    'error_reporting' => $env->getString('PHP_ERROR_REPORTING', 'E_ALL'),
    'memory_limit' => $env->getString('PHP_MEMORY_LIMIT', '-1'),
    'max_execution_time' => $env->getInt('PHP_MAX_EXECUTION_TIME', 0),
];
