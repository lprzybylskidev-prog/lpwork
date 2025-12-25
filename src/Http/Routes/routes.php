<?php
declare(strict_types=1);

use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;

/** @var \LPwork\Http\Routing\RouteCollection $routes */

$routes->get(
    '/error/{code:\d+}/{id}',
    static function (ServerRequestInterface $request, int $code, string $id): Response {
        return new Response(
            $code,
            ['Content-Type' => 'text/plain'],
            \sprintf('Error %d (ID: %s)', $code, $id),
        );
    },
    'error.generic',
);
