<?php
declare(strict_types=1);

use LPwork\Http\Routing\RouteCollection;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;

/** @var RouteCollection $routes */

$routes->get(
    "/error/{code:\\d+}/{id}",
    static function (ServerRequestInterface $request): Response {
        $params = $request->getAttribute("route_params", []);
        $code = (int) ($params["code"] ?? 500);
        $errorId = $params["id"] ?? "";

        return new Response(
            $code,
            ["Content-Type" => "text/plain"],
            \sprintf("Error %d (ID: %s)", $code, $errorId),
        );
    },
    "error.generic",
);
