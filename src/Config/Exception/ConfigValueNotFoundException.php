<?php
declare(strict_types=1);

namespace LPwork\Config\Exception;

/**
 * Thrown when a configuration value is missing and no default was provided.
 */
class ConfigValueNotFoundException extends \RuntimeException {}
