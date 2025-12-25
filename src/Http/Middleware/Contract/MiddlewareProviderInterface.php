<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware\Contract;

/**
 * Provides middleware stack entries for the HTTP runtime.
 */
interface MiddlewareProviderInterface
{
    /**
     * Returns middlewares in registration order.
     *
     * @return array<int, \Psr\Http\Server\MiddlewareInterface>
     */
    public function getMiddlewares(): array;
}
