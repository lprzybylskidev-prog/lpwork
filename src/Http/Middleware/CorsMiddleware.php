<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware;

use LPwork\Http\HttpConfiguration;
use LPwork\Http\Response\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds CORS headers and handles preflight requests.
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @var HttpConfiguration
     */
    private HttpConfiguration $config;

    /**
     * @var JsonResponseFactory
     */
    private JsonResponseFactory $jsonResponseFactory;

    /**
     * @param HttpConfiguration   $config
     * @param JsonResponseFactory $jsonResponseFactory
     */
    public function __construct(HttpConfiguration $config, JsonResponseFactory $jsonResponseFactory)
    {
        $this->config = $config;
        $this->jsonResponseFactory = $jsonResponseFactory;
    }

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (!$this->config->corsEnabled()) {
            return $handler->handle($request);
        }

        $origin = $request->getHeaderLine('Origin');

        if ($origin === '') {
            return $handler->handle($request);
        }

        if (!$this->isOriginAllowed($origin)) {
            return $handler->handle($request);
        }

        if (\strtoupper($request->getMethod()) === 'OPTIONS') {
            return $this->handlePreflight($request, $origin);
        }

        $response = $handler->handle($request);

        return $this->applyCorsHeaders($response, $origin);
    }

    /**
     * @param ServerRequestInterface $request
     * @param string                 $origin
     *
     * @return ResponseInterface
     */
    private function handlePreflight(
        ServerRequestInterface $request,
        string $origin,
    ): ResponseInterface {
        $response = $this->jsonResponseFactory->empty(204);

        $response = $this->applyCorsHeaders($response, $origin);
        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');

        if ($requestHeaders !== '') {
            $response = $response->withHeader('Access-Control-Allow-Headers', $requestHeaders);
        } elseif ($this->config->corsAllowHeaders() !== []) {
            $response = $response->withHeader(
                'Access-Control-Allow-Headers',
                \implode(',', $this->config->corsAllowHeaders()),
            );
        }

        $allowMethods = $this->config->corsAllowMethods();

        if ($allowMethods !== []) {
            $response = $response->withHeader(
                'Access-Control-Allow-Methods',
                \implode(',', $allowMethods),
            );
        }

        if ($this->config->corsMaxAge() > 0) {
            $response = $response->withHeader(
                'Access-Control-Max-Age',
                (string) $this->config->corsMaxAge(),
            );
        }

        return $response;
    }

    /**
     * @param ResponseInterface $response
     * @param string            $origin
     *
     * @return ResponseInterface
     */
    private function applyCorsHeaders(
        ResponseInterface $response,
        string $origin,
    ): ResponseInterface {
        $allowOrigin = $this->resolveAllowOrigin($origin);
        $response = $response->withHeader('Access-Control-Allow-Origin', $allowOrigin);

        if ($this->config->corsAllowCredentials()) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if ($this->config->corsExposeHeaders() !== []) {
            $response = $response->withHeader(
                'Access-Control-Expose-Headers',
                \implode(',', $this->config->corsExposeHeaders()),
            );
        }

        return $response->withAddedHeader('Vary', 'Origin');
    }

    /**
     * @param string $origin
     *
     * @return bool
     */
    private function isOriginAllowed(string $origin): bool
    {
        $allowed = $this->config->corsAllowOrigin();

        if (\in_array('*', $allowed, true)) {
            return true;
        }

        return \in_array($origin, $allowed, true);
    }

    /**
     * @param string $origin
     *
     * @return string
     */
    private function resolveAllowOrigin(string $origin): string
    {
        if ($this->config->corsAllowCredentials()) {
            return $origin;
        }

        if (\in_array('*', $this->config->corsAllowOrigin(), true)) {
            return '*';
        }

        return $origin;
    }
}
