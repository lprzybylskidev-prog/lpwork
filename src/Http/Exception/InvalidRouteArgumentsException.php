<?php
declare(strict_types=1);

namespace LPwork\Http\Exception;

/**
 * Thrown when route parameters cannot be resolved for a handler.
 */
final class InvalidRouteArgumentsException extends \RuntimeException {}
