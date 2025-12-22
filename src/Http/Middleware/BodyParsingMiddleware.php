<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware;

use LPwork\Http\HttpConfiguration;
use LPwork\Http\Response\JsonResponseFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Parses common request bodies (JSON, url-encoded) into parsedBody.
 */
class BodyParsingMiddleware implements MiddlewareInterface
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
     * @param HttpConfiguration    $config
     * @param JsonResponseFactory  $jsonResponseFactory
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
        if (!$this->config->bodyParsingEnabled()) {
            return $handler->handle($request);
        }

        if ($request->getParsedBody() !== null) {
            return $handler->handle($request);
        }

        $contentType = \strtolower(\trim($request->getHeaderLine('Content-Type')));

        if (!$this->isAllowedContentType($contentType)) {
            return $handler->handle($request);
        }

        $body = (string) $request->getBody();

        if ($this->config->maxBodySize() > 0 && \strlen($body) > $this->config->maxBodySize()) {
            return $this->jsonResponseFactory->json(['error' => 'Request body too large.'], 413);
        }

        if ($body === '') {
            return $handler->handle($request);
        }

        if ($this->isJson($contentType)) {
            $parsed = $this->decodeJson($body);

            if ($parsed === null) {
                if ($this->config->rejectInvalidJson()) {
                    return $this->jsonResponseFactory->json(
                        ['error' => 'Invalid JSON payload.'],
                        400,
                    );
                }

                return $handler->handle($request);
            }

            $request = $request->withParsedBody($parsed);

            return $handler->handle($request);
        }

        if ($this->isFormUrlEncoded($contentType)) {
            $parsed = [];
            \parse_str($body, $parsed);
            $request = $request->withParsedBody($parsed);

            return $handler->handle($request);
        }

        return $handler->handle($request);
    }

    /**
     * @param string $contentType
     *
     * @return bool
     */
    private function isAllowedContentType(string $contentType): bool
    {
        foreach ($this->config->allowedContentTypes() as $allowed) {
            if ($allowed === '') {
                continue;
            }

            if (\stripos($contentType, $allowed) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $contentType
     *
     * @return bool
     */
    private function isJson(string $contentType): bool
    {
        return \str_contains($contentType, 'json');
    }

    /**
     * @param string $contentType
     *
     * @return bool
     */
    private function isFormUrlEncoded(string $contentType): bool
    {
        return \str_starts_with($contentType, 'application/x-www-form-urlencoded');
    }

    /**
     * @param string $body
     *
     * @return mixed[]|object|null
     */
    private function decodeJson(string $body): array|object|null
    {
        $depth = $this->config->jsonMaxDepth();

        if ($depth < 1) {
            $depth = 1;
        }

        try {
            $decoded = \json_decode(
                $body,
                $this->config->jsonAssoc(),
                $depth,
                \JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException) {
            return null;
        }

        if (\is_array($decoded) || \is_object($decoded)) {
            return $decoded;
        }

        return null;
    }
}
