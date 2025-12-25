<?php
declare(strict_types=1);

namespace LPwork\Http\Routing\Exception;

/**
 * Thrown when no route matches the incoming request.
 */
class RouteNotFoundException extends \RuntimeException
{
    /**
     * @param string $method
     * @param string $path
     */
    public function __construct(string $method, string $path)
    {
        parent::__construct(\sprintf('Route not found for %s %s', $method, $path), 404);
    }
}
