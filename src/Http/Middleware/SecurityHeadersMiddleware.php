<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware;

use LPwork\Security\SecurityConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds configurable security-related response headers.
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * @var SecurityConfiguration
     */
    private SecurityConfiguration $config;

    /**
     * @param SecurityConfiguration $config
     */
    public function __construct(SecurityConfiguration $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $handler->handle($request);

        if (!$this->config->headersEnabled()) {
            return $response;
        }

        if ($this->config->contentTypeOptions()) {
            $response = $response->withHeader('X-Content-Type-Options', 'nosniff');
        }

        $frameOptions = $this->config->frameOptions();

        if ($frameOptions !== '') {
            $response = $response->withHeader('X-Frame-Options', $frameOptions);
        }

        $referrerPolicy = $this->config->referrerPolicy();

        if ($referrerPolicy !== '') {
            $response = $response->withHeader('Referrer-Policy', $referrerPolicy);
        }

        $permissionsPolicy = $this->config->permissionsPolicy();

        if ($permissionsPolicy !== '') {
            $response = $response->withHeader('Permissions-Policy', $permissionsPolicy);
        }

        return $response;
    }
}
