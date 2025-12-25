<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware;

use LPwork\Http\Middleware\Contract\MiddlewareProviderInterface;
use LPwork\Http\Middleware\BodyParsingMiddleware;
use LPwork\Http\Middleware\CorsMiddleware;
use LPwork\Http\Middleware\CsrfMiddleware;
use LPwork\Http\Middleware\ErrorHandlingMiddleware;
use LPwork\Http\Middleware\Routing\RouteDispatchMiddleware;
use LPwork\Http\Middleware\Routing\RouteMatchMiddleware;
use LPwork\Http\Middleware\SecurityHeadersMiddleware;
use LPwork\Http\Middleware\SessionMiddleware;
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
            $this->container->get(ErrorHandlingMiddleware::class),
            $this->container->get(CorsMiddleware::class),
            $this->container->get(SecurityHeadersMiddleware::class),
            $this->container->get(SessionMiddleware::class),
            $this->container->get(CsrfMiddleware::class),
            $this->container->get(BodyParsingMiddleware::class),
            $this->container->get(RouteMatchMiddleware::class),
            $this->container->get(RouteDispatchMiddleware::class),
        ];
    }
}
