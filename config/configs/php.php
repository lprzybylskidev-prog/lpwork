<?php
declare(strict_types=1);

use LPwork\Environment\Env;

/** @var Env $env */

return [
    /**
     * PHP runtime configuration loaded at bootstrap.
     * error_log: target file for PHP errors; empty keeps php.ini default.
     * error_reporting: error reporting level string, e.g. E_ALL or E_ALL & ~E_NOTICE.
     * memory_limit: memory cap (e.g. 256M); -1 disables the limit.
     * max_execution_time: execution time limit in seconds; 0 disables the limit.
     */
    // Path to PHP error log file; empty uses php.ini.
    'error_log' => $env->getString('PHP_ERROR_LOG', ''),
    // PHP error reporting level string.
    'error_reporting' => $env->getString('PHP_ERROR_REPORTING', 'E_ALL'),
    // Memory limit for PHP scripts.
    'memory_limit' => $env->getString('PHP_MEMORY_LIMIT', '-1'),
    // Max execution time in seconds.
    'max_execution_time' => $env->getInt('PHP_MAX_EXECUTION_TIME', 0),
];
