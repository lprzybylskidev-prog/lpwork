<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware\Contract;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Provides middleware stack entries for the HTTP runtime.
 */
interface MiddlewareProviderInterface
{
    /**
     * Returns middlewares in registration order.
     *
     * @return array<int, MiddlewareInterface>
     */
    public function getMiddlewares(): array;
}
