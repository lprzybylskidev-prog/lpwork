<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware;

use LPwork\Http\Middleware\Contract\MiddlewareProviderInterface;
use LPwork\Http\Middleware\Routing\RouteDispatchMiddleware;
use LPwork\Http\Middleware\Routing\RouteMatchMiddleware;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Container\ContainerInterface;

/**
 * Provides built-in HTTP middlewares required for routing.
 */
class MiddlewareProvider implements MiddlewareProviderInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function getMiddlewares(): array
    {
        return [
            $this->container->get(RouteMatchMiddleware::class),
            $this->container->get(RouteDispatchMiddleware::class),
        ];
    }
}
