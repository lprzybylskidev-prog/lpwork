<?php
declare(strict_types=1);

namespace LPwork\Kernel;

use LPwork\Http\Contract\ResponseEmitterInterface;
use LPwork\Http\Middleware\Contract\MiddlewareProviderInterface;
use LPwork\Http\Middleware\MiddlewareProvider as BuiltinMiddlewareProvider;
use Nyholm\Psr7\Response;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Handles the HTTP runtime lifecycle.
 */
class HttpKernel
{
    /**
     * @var ServerRequestCreator
     */
    private ServerRequestCreator $serverRequestCreator;

    /**
     * @var BuiltinMiddlewareProvider
     */
    private BuiltinMiddlewareProvider $builtinMiddlewareProvider;

    /**
     * @var MiddlewareProviderInterface
     */
    private MiddlewareProviderInterface $appMiddlewareProvider;

    /**
     * @var ResponseEmitterInterface
     */
    private ResponseEmitterInterface $responseEmitter;

    /**
     * @param ServerRequestCreator        $serverRequestCreator
     * @param BuiltinMiddlewareProvider   $builtinMiddlewareProvider
     * @param MiddlewareProviderInterface $appMiddlewareProvider
     * @param ResponseEmitterInterface    $responseEmitter
     */
    public function __construct(
        ServerRequestCreator $serverRequestCreator,
        BuiltinMiddlewareProvider $builtinMiddlewareProvider,
        MiddlewareProviderInterface $appMiddlewareProvider,
        ResponseEmitterInterface $responseEmitter,
    ) {
        $this->serverRequestCreator = $serverRequestCreator;
        $this->builtinMiddlewareProvider = $builtinMiddlewareProvider;
        $this->appMiddlewareProvider = $appMiddlewareProvider;
        $this->responseEmitter = $responseEmitter;
    }

    /**
     * Boots and runs the HTTP kernel.
     *
     * @return void
     */
    public function run(): void
    {
        $request = $this->serverRequestCreator->fromGlobals();

        $middlewares = $this->mergeMiddlewares(
            $this->builtinMiddlewareProvider->getMiddlewares(),
            $this->appMiddlewareProvider->getMiddlewares(),
        );

        $response = $this->handle($request, $middlewares);

        $this->responseEmitter->emit($response, $request);
    }

    /**
     * @param ServerRequestInterface        $request
     * @param array<int, MiddlewareInterface> $middlewares
     *
     * @return ResponseInterface
     */
    private function handle(ServerRequestInterface $request, array $middlewares): ResponseInterface
    {
        $handler = new class implements RequestHandlerInterface {
            /**
             * @inheritDoc
             */
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(500, [], 'No middleware able to handle the request.');
            }
        };

        foreach (\array_reverse($middlewares) as $middleware) {
            $handler = new class ($middleware, $handler) implements RequestHandlerInterface {
                /**
                 * @var MiddlewareInterface
                 */
                private MiddlewareInterface $middleware;

                /**
                 * @var RequestHandlerInterface
                 */
                private RequestHandlerInterface $next;

                /**
                 * @param MiddlewareInterface     $middleware
                 * @param RequestHandlerInterface $next
                 */
                public function __construct(
                    MiddlewareInterface $middleware,
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

        return $handler->handle($request);
    }

    /**
     * Merges middleware stacks, allowing application middlewares to override by class name.
     *
     * @param array<int, MiddlewareInterface> $builtin
     * @param array<int, MiddlewareInterface> $app
     *
     * @return array<int, MiddlewareInterface>
     */
    private function mergeMiddlewares(array $builtin, array $app): array
    {
        $byName = [];

        foreach ($builtin as $middleware) {
            $byName[\get_class($middleware)] = $middleware;
        }

        foreach ($app as $middleware) {
            $byName[\get_class($middleware)] = $middleware;
        }

        return \array_values($byName);
    }
}
