<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware\Routing;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;
use LPwork\Http\Request\RequestContext;
use LPwork\Http\Routing\Contract\HandlerArgumentResolverInterface;
use LPwork\Http\Routing\Contract\RouteHandlerResolverInterface;

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
     * @var RouteHandlerResolverInterface
     */
    private RouteHandlerResolverInterface $handlerResolver;

    /**
     * @var HandlerArgumentResolverInterface
     */
    private HandlerArgumentResolverInterface $argumentResolver;

    /**
     * @param ContainerInterface              $container
     * @param RouteHandlerResolverInterface   $handlerResolver
     * @param HandlerArgumentResolverInterface $argumentResolver
     */
    public function __construct(
        ContainerInterface $container,
        RouteHandlerResolverInterface $handlerResolver,
        HandlerArgumentResolverInterface $argumentResolver,
    )
    {
        $this->container = $container;
        $this->handlerResolver = $handlerResolver;
        $this->argumentResolver = $argumentResolver;
    }

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var RequestContext|null $context */
        $context = $request->getAttribute(RequestContext::ATTRIBUTE);

        if ($context === null) {
            return new Response(500, [], 'Route handler not resolved');
        }

        $routeHandler = $context->handler();

        if ($routeHandler === null) {
            return new Response(500, [], 'Route handler not resolved');
        }

        $resolvedHandler = $this->handlerResolver->resolve($routeHandler);

        if ($resolvedHandler === null) {
            return new Response(500, [], 'Route handler not callable');
        }

        /** @var array<int, callable|string|\Psr\Http\Server\MiddlewareInterface> $routeMiddleware */
        $routeMiddleware = $context->middleware();

        $routeParams = $context->parameters();

        $finalHandler = new class ($resolvedHandler, $this->argumentResolver, $routeParams)
            implements RequestHandlerInterface
        {
            /**
             * @var callable
             */
            private $routeHandler;

            /**
             * @var HandlerArgumentResolver
             */
            private HandlerArgumentResolver $argumentResolver;

            /**
             * @var array<string, string>
             */
            private array $routeParams;

            /**
             * @param callable $routeHandler
             * @param HandlerArgumentResolver $argumentResolver
             * @param array<string, string> $routeParams
             */
            public function __construct(
                callable $routeHandler,
                HandlerArgumentResolver $argumentResolver,
                array $routeParams,
            ) {
                $this->routeHandler = $routeHandler;
                $this->argumentResolver = $argumentResolver;
                $this->routeParams = $routeParams;
            }

            /**
             * @inheritDoc
             */
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                /** @var callable $handler */
                $handler = $this->routeHandler;
                $args = $this->argumentResolver->resolveArguments(
                    $handler,
                    $request,
                    $this->routeParams,
                );
                $response = $handler(...$args);

                if (!$response instanceof ResponseInterface) {
                    return new Response(500, [], 'Invalid route response');
                }

                return $response;
            }
        };

        $composedHandler = $this->wrapRouteMiddlewares($routeMiddleware, $finalHandler);

        return $composedHandler->handle($request);
    }

    /**
     * @param array<int, callable|string|\Psr\Http\Server\MiddlewareInterface> $middlewareClassNames
     * @param RequestHandlerInterface                                          $finalHandler
     *
     * @return RequestHandlerInterface
     */
    private function wrapRouteMiddlewares(
        array $middlewareClassNames,
        RequestHandlerInterface $finalHandler,
    ): RequestHandlerInterface {
        $handler = $finalHandler;

        foreach (\array_reverse($middlewareClassNames) as $middlewareDefinition) {
            $middleware = $this->instantiateMiddleware($middlewareDefinition);

            $handler = new class ($middleware, $handler) implements RequestHandlerInterface {
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
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->middleware->process($request, $this->next);
                }
            };
        }

        return $handler;
    }

    /**
     * @param callable|string|\Psr\Http\Server\MiddlewareInterface $middlewareDefinition
     *
     * @return \Psr\Http\Server\MiddlewareInterface
     */
    private function instantiateMiddleware(
        callable|string|\Psr\Http\Server\MiddlewareInterface $middlewareDefinition,
    ): \Psr\Http\Server\MiddlewareInterface {
        if ($middlewareDefinition instanceof \Psr\Http\Server\MiddlewareInterface) {
            return $middlewareDefinition;
        }

        if (\is_callable($middlewareDefinition) && !\is_string($middlewareDefinition)) {
            $resolved = $middlewareDefinition($this->container);

            if ($resolved instanceof \Psr\Http\Server\MiddlewareInterface) {
                return $resolved;
            }

            return new class implements \Psr\Http\Server\MiddlewareInterface {
                /**
                 * @inheritDoc
                 */
                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler,
                ): ResponseInterface {
                    return new Response(500, [], 'Invalid route middleware');
                }
            };
        }

        if (!\is_string($middlewareDefinition) || !\class_exists($middlewareDefinition)) {
            return new class implements \Psr\Http\Server\MiddlewareInterface {
                /**
                 * @inheritDoc
                 */
                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler,
                ): ResponseInterface {
                    return new Response(500, [], 'Route middleware not found');
                }
            };
        }

        $middleware = $this->container->get($middlewareDefinition);

        if (!$middleware instanceof \Psr\Http\Server\MiddlewareInterface) {
            return new class implements \Psr\Http\Server\MiddlewareInterface {
                /**
                 * @inheritDoc
                 */
                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler,
                ): ResponseInterface {
                    return new Response(500, [], 'Invalid route middleware');
                }
            };
        }

        return $middleware;
    }
}
