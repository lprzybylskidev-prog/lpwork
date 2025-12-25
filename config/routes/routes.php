<?php
declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;
use LPwork\Http\Response\ResponseFactory;

/** @var \LPwork\Http\Routing\RouteCollection $routes */

$routes->get(
    '/',
    static function (
        ServerRequestInterface $request,
        ResponseFactory $responses,
    ): \Psr\Http\Message\ResponseInterface {
        $name = $request->getQueryParams()['name'] ?? 'world';

        return $responses->json(['message' => \sprintf('Hello, %s!', $name)]);
    },
    'hello',
);
