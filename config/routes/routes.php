<?php
declare(strict_types=1);

use LPwork\Http\Routing\RouteCollection;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;

/** @var RouteCollection $routes */

$routes->get(
    "/",
    static function (ServerRequestInterface $request): Response {
        $name = $request->getQueryParams()["name"] ?? "world";

        return new Response(
            200,
            ["Content-Type" => "application/json"],
            \json_encode(
                ["message" => \sprintf("Hello, %s!", $name)],
                \JSON_THROW_ON_ERROR,
            ),
        );
    },
    "hello",
);
