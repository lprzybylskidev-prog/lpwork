<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware;

use LPwork\Http\Error\ErrorResponseBuilder;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Captures errors and normalizes error responses for API and HTML contexts.
 */
class ErrorHandlingMiddleware implements MiddlewareInterface
{
    /**
     * @var ErrorResponseBuilder
     */
    private ErrorResponseBuilder $responseBuilder;

    /**
     * @param ErrorResponseBuilder $responseBuilder
     */
    public function __construct(ErrorResponseBuilder $responseBuilder)
    {
        $this->responseBuilder = $responseBuilder;
    }

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        try {
            $response = $handler->handle($request);

            if ($this->isErrorStatus($response->getStatusCode())) {
                return $this->renderError(
                    $request,
                    $response->getStatusCode(),
                    $response->getReasonPhrase() ?: null,
                    null,
                );
            }

            return $response;
        } catch (\Throwable $throwable) {
            return $this->renderError(
                $request,
                500,
                $throwable->getMessage(),
                $throwable,
            );
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param int                    $status
     * @param string|null            $message
     * @param \Throwable|null        $throwable
     *
     * @return ResponseInterface
     */
    private function renderError(
        ServerRequestInterface $request,
        int $status,
        ?string $message,
        ?\Throwable $throwable,
    ): ResponseInterface {
        $errorId = \bin2hex(\random_bytes(16));

        if ($this->wantsJson($request)) {
            return $this->responseBuilder->buildApiError(
                $request,
                $status,
                $errorId,
                $message,
                $throwable,
            );
        }

        return $this->responseBuilder->buildHtmlError(
            $request,
            $status,
            $errorId,
            $throwable,
        );
    }

    /**
     * @param int $status
     *
     * @return bool
     */
    private function isErrorStatus(int $status): bool
    {
        return $status >= 400 && $status <= 599;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function wantsJson(ServerRequestInterface $request): bool
    {
        $accept = $request->getHeaderLine("Accept");

        return \stripos($accept, "application/json") !== false;
    }
}
