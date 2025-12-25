<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware\Routing;

use FastRoute\Dispatcher;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LPwork\Http\Request\RequestContext;
use LPwork\Http\Request\RequestContextStore;
use LPwork\Http\Routing\Exception\MethodNotAllowedException;
use LPwork\Http\Routing\Exception\RouteNotFoundException;

/**
 * Matches the incoming request to a route definition.
 */
class RouteMatchMiddleware implements MiddlewareInterface
{
    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @param Dispatcher $dispatcher
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $result = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

        switch ($result[0]) {
            case Dispatcher::NOT_FOUND:
                throw new RouteNotFoundException(
                    $request->getMethod(),
                    $request->getUri()->getPath(),
                );
            case Dispatcher::METHOD_NOT_ALLOWED:
                /** @var array<int, string> $allowed */
                $allowed = $result[1] ?? [];
                throw new MethodNotAllowedException(
                    $request->getMethod(),
                    $request->getUri()->getPath(),
                    $allowed,
                );
            case Dispatcher::FOUND:
                $routeInfo = $result[1];
                $params = $result[2];

                $context = new RequestContext(
                    $routeInfo['name'],
                    $routeInfo['handler'],
                    $routeInfo['middleware'],
                    $params,
                );

                $request = $request
                    ->withAttribute(RequestContext::ATTRIBUTE, $context)
                    ->withAttribute('route.params', $params);
                RequestContextStore::set($context);

                return $handler->handle($request);
        }

        return new Response(500, [], 'Routing error');
    }
}
