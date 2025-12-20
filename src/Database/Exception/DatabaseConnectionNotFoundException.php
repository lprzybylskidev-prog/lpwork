<?php
declare(strict_types=1);

namespace LPwork\Database\Exception;

/**
 * Thrown when a requested database connection is not configured.
 */
class DatabaseConnectionNotFoundException extends \RuntimeException {}
