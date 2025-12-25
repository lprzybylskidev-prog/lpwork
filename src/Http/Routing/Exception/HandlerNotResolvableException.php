<?php
declare(strict_types=1);

namespace LPwork\Http\Routing\Exception;

/**
 * Thrown when a route handler or middleware cannot be resolved.
 */
class HandlerNotResolvableException extends \RuntimeException {}
