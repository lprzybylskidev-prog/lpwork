<?php
declare(strict_types=1);

namespace LPwork\Redis\Exception;

/**
 * Thrown when a requested Redis connection is not defined.
 */
class RedisConnectionNotFoundException extends \RuntimeException {}
