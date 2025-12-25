<?php
declare(strict_types=1);

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @var \LPwork\Http\Routing\RouteCollection $routes */

$routes->get(
    '/',
    static function (ServerRequestInterface $request): ResponseInterface {
        $name = $request->getQueryParams()['name'] ?? 'world';

        $body = \json_encode(['message' => \sprintf('Hello, %s!', $name)], \JSON_THROW_ON_ERROR);

        return new Response(200, ['Content-Type' => 'application/json'], $body);
    },
    'hello',
);
