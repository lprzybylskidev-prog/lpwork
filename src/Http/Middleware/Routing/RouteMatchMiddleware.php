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
                return new Response(404, [], 'Not Found');
            case Dispatcher::METHOD_NOT_ALLOWED:
                return new Response(405, [], 'Method Not Allowed');
            case Dispatcher::FOUND:
                $routeInfo = $result[1];
                $params = $result[2];

                $context = new RequestContext(
                    $routeInfo['name'],
                    $routeInfo['handler'],
                    $routeInfo['middleware'],
                    $params,
                );

                $request = $request->withAttribute(RequestContext::ATTRIBUTE, $context);
                RequestContextStore::set($context);

                return $handler->handle($request);
        }

        return new Response(500, [], 'Routing error');
    }
}
