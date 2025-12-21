<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware;

use LPwork\ErrorLog\Contract\ErrorIdProviderInterface;
use LPwork\ErrorLog\Contract\ErrorLoggerInterface;
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
     * @var ErrorLoggerInterface
     */
    private ErrorLoggerInterface $errorLogger;

    /**
     * @var ErrorIdProviderInterface
     */
    private ErrorIdProviderInterface $errorIdProvider;

    /**
     * @param ErrorResponseBuilder $responseBuilder
     * @param ErrorLoggerInterface $errorLogger
     * @param ErrorIdProviderInterface $errorIdProvider
     */
    public function __construct(
        ErrorResponseBuilder $responseBuilder,
        ErrorLoggerInterface $errorLogger,
        ErrorIdProviderInterface $errorIdProvider,
    ) {
        $this->responseBuilder = $responseBuilder;
        $this->errorLogger = $errorLogger;
        $this->errorIdProvider = $errorIdProvider;
    }

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $this->errorIdProvider->clear();

        try {
            $response = $handler->handle($request);

            if ($this->isErrorStatus($response->getStatusCode())) {
                return $this->renderError(
                    $request,
                    $response->getStatusCode(),
                    $response->getReasonPhrase() ?: null,
                    null,
                    null,
                );
            }

            return $response;
        } catch (\Throwable $throwable) {
            $errorId = $this->errorLogger->log(
                $throwable,
                $this->buildHttpContext($request),
            );

            return $this->renderError(
                $request,
                500,
                $throwable->getMessage(),
                $throwable,
                $errorId,
            );
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param int                    $status
     * @param string|null            $message
     * @param \Throwable|null        $throwable
     * @param string|null            $errorId
     *
     * @return ResponseInterface
     */
    private function renderError(
        ServerRequestInterface $request,
        int $status,
        ?string $message,
        ?\Throwable $throwable,
        ?string $errorId,
    ): ResponseInterface {
        $id = $errorId ?? \bin2hex(\random_bytes(16));
        $this->errorIdProvider->setCurrentErrorId($id);

        if ($this->wantsJson($request)) {
            return $this->responseBuilder->buildApiError(
                $request,
                $status,
                $id,
                $message,
                $throwable,
            );
        }

        return $this->responseBuilder->buildHtmlError(
            $request,
            $status,
            $id,
            $throwable,
        );
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array<string, mixed>
     */
    private function buildHttpContext(ServerRequestInterface $request): array
    {
        return [
            "runtime" => "http",
            "method" => $request->getMethod(),
            "uri" => (string) $request->getUri(),
            "headers" => $request->getHeaders(),
        ];
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
