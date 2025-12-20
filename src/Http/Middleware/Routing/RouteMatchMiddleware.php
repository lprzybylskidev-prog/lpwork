<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware\Routing;

use FastRoute\Dispatcher;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
        $result = $this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath(),
        );

        switch ($result[0]) {
            case Dispatcher::NOT_FOUND:
                return new Response(404, [], "Not Found");
            case Dispatcher::METHOD_NOT_ALLOWED:
                return new Response(405, [], "Method Not Allowed");
            case Dispatcher::FOUND:
                $routeInfo = $result[1];
                $params = $result[2];

                $request = $request
                    ->withAttribute("route_handler", $routeInfo["handler"])
                    ->withAttribute("route_name", $routeInfo["name"])
                    ->withAttribute(
                        "route_middleware",
                        $routeInfo["middleware"],
                    )
                    ->withAttribute("route_params", $params);

                return $handler->handle($request);
        }

        return new Response(500, [], "Routing error");
    }
}
