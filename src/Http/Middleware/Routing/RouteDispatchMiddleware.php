<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware\Routing;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;

/**
 * Dispatches a matched route handler, applying route-specific middlewares.
 */
class RouteDispatchMiddleware implements MiddlewareInterface
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
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $routeHandler = $request->getAttribute("route_handler");

        if ($routeHandler === null) {
            return new Response(500, [], "Route handler not resolved");
        }

        /** @var array<int, string> $routeMiddleware */
        $routeMiddleware = $request->getAttribute("route_middleware", []);

        $finalHandler = new class ($routeHandler) implements
            RequestHandlerInterface
        {
            /**
             * @var callable
             */
            private $routeHandler;

            /**
             * @param callable $routeHandler
             */
            public function __construct(callable $routeHandler)
            {
                $this->routeHandler = $routeHandler;
            }

            /**
             * @inheritDoc
             */
            public function handle(
                ServerRequestInterface $request,
            ): ResponseInterface {
                /** @var callable $handler */
                $handler = $this->routeHandler;
                $response = $handler($request);

                if (!$response instanceof ResponseInterface) {
                    return new Response(500, [], "Invalid route response");
                }

                return $response;
            }
        };

        $composedHandler = $this->wrapRouteMiddlewares(
            $routeMiddleware,
            $finalHandler,
        );

        return $composedHandler->handle($request);
    }

    /**
     * @param array<int, string>      $middlewareClassNames
     * @param RequestHandlerInterface $finalHandler
     *
     * @return RequestHandlerInterface
     */
    private function wrapRouteMiddlewares(
        array $middlewareClassNames,
        RequestHandlerInterface $finalHandler,
    ): RequestHandlerInterface {
        $handler = $finalHandler;

        foreach (\array_reverse($middlewareClassNames) as $middlewareClass) {
            $middleware = $this->instantiateMiddleware($middlewareClass);

            $handler = new class ($middleware, $handler) implements
                RequestHandlerInterface
            {
                /**
                 * @var \Psr\Http\Server\MiddlewareInterface
                 */
                private $middleware;

                /**
                 * @var RequestHandlerInterface
                 */
                private RequestHandlerInterface $next;

                /**
                 * @param \Psr\Http\Server\MiddlewareInterface $middleware
                 * @param RequestHandlerInterface              $next
                 */
                public function __construct(
                    \Psr\Http\Server\MiddlewareInterface $middleware,
                    RequestHandlerInterface $next,
                ) {
                    $this->middleware = $middleware;
                    $this->next = $next;
                }

                /**
                 * @inheritDoc
                 */
                public function handle(
                    ServerRequestInterface $request,
                ): ResponseInterface {
                    return $this->middleware->process($request, $this->next);
                }
            };
        }

        return $handler;
    }

    /**
     * @param string $middlewareClass
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    private function instantiateMiddleware(
        string $middlewareClass,
    ): \Psr\Http\Server\MiddlewareInterface {
        if (!\class_exists($middlewareClass)) {
            return new class implements \Psr\Http\Server\MiddlewareInterface {
                /**
                 * @inheritDoc
                 */
                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler,
                ): ResponseInterface {
                    return new Response(500, [], "Route middleware not found");
                }
            };
        }

        $middleware = $this->container->get($middlewareClass);

        if (!$middleware instanceof \Psr\Http\Server\MiddlewareInterface) {
            return new class implements \Psr\Http\Server\MiddlewareInterface {
                /**
                 * @inheritDoc
                 */
                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler,
                ): ResponseInterface {
                    return new Response(500, [], "Invalid route middleware");
                }
            };
        }

        return $middleware;
    }
}
