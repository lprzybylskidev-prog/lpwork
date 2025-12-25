<?php
declare(strict_types=1);

namespace Config;

use LPwork\Http\Middleware\Contract\MiddlewareProviderInterface;

/**
 * Application-level HTTP middleware provider.
 */
class MiddlewareProvider implements MiddlewareProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getMiddlewares(): array
    {
        /** @var array<int, \Psr\Http\Server\MiddlewareInterface> $middlewares */
        $middlewares = [];

        return $middlewares;
    }
}
