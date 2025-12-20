<?php
declare(strict_types=1);

namespace LPwork\Environment\Exception;

/**
 * Thrown when a requested environment variable is missing.
 */
class EnvValueNotFoundException extends \RuntimeException
{
}
