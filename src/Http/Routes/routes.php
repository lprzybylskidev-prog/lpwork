<?php
declare(strict_types=1);

use LPwork\Http\Request\RequestContext;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;

/** @var \LPwork\Http\Routing\RouteCollection $routes */

$routes->get(
    '/error/{code:\\d+}/{id}',
    static function (ServerRequestInterface $request): Response {
        $context = $request->getAttribute(RequestContext::ATTRIBUTE);
        $params = [];

        if ($context instanceof \LPwork\Http\Request\RequestContext) {
            $params = $context->parameters();
        }

        $code = (int) ($params['code'] ?? 500);
        $errorId = $params['id'] ?? '';

        return new Response(
            $code,
            ['Content-Type' => 'text/plain'],
            \sprintf('Error %d (ID: %s)', $code, $errorId),
        );
    },
    'error.generic',
);
