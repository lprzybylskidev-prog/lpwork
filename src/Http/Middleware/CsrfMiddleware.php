<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware;

use LPwork\Http\Response\JsonResponseFactory;
use LPwork\Http\Session\Contract\SessionInterface;
use LPwork\Security\Csrf\CsrfTokenSessionStorage;
use LPwork\Security\SecurityConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

/**
 * Verifies CSRF tokens for mutating HTTP methods.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * @var SecurityConfiguration
     */
    private SecurityConfiguration $config;

    /**
     * @var JsonResponseFactory
     */
    private JsonResponseFactory $jsonResponseFactory;

    /**
     * @param SecurityConfiguration $config
     * @param JsonResponseFactory   $jsonResponseFactory
     */
    public function __construct(
        SecurityConfiguration $config,
        JsonResponseFactory $jsonResponseFactory,
    ) {
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
        if (!$this->config->csrfEnabled()) {
            return $handler->handle($request);
        }

        if (!$this->isProtectedMethod($request) || $this->isExcludedPath($request)) {
            return $handler->handle($request);
        }

        $session = $request->getAttribute(SessionMiddleware::ATTRIBUTE);

        if (!$session instanceof SessionInterface) {
            return $this->jsonResponseFactory->json(
                ['error' => 'CSRF validation failed (no session).'],
                403,
            );
        }

        $tokenValue = $this->extractToken($request);

        if ($tokenValue === null) {
            return $this->jsonResponseFactory->json(['error' => 'CSRF token is missing.'], 403);
        }

        $manager = new CsrfTokenManager(null, new CsrfTokenSessionStorage($session));
        $token = new CsrfToken($this->config->csrfTokenId(), $tokenValue);

        if (!$manager->isTokenValid($token)) {
            return $this->jsonResponseFactory->json(['error' => 'Invalid CSRF token.'], 403);
        }

        return $handler->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function isProtectedMethod(ServerRequestInterface $request): bool
    {
        return \in_array(\strtoupper($request->getMethod()), $this->config->csrfMethods(), true);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function isExcludedPath(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        foreach ($this->config->csrfExcludePaths() as $prefix) {
            if (\str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    private function extractToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine($this->config->csrfHeader());

        if ($header !== '') {
            return $header;
        }

        $param = $this->config->csrfParameter();

        $parsed = $request->getParsedBody();

        if (\is_array($parsed) && isset($parsed[$param]) && \is_scalar($parsed[$param])) {
            return (string) $parsed[$param];
        }

        $query = $request->getQueryParams();

        if (isset($query[$param]) && \is_scalar($query[$param])) {
            return (string) $query[$param];
        }

        return null;
    }
}
