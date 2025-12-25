<?php
declare(strict_types=1);

namespace LPwork\Http\Middleware;

use LPwork\ErrorLog\Contract\ErrorIdProviderInterface;
use LPwork\ErrorLog\Contract\ErrorLoggerInterface;
use LPwork\ErrorLog\ErrorLogConfiguration;
use LPwork\Http\Error\ErrorResponseBuilder;
use LPwork\Http\Error\ErrorContextFactory;
use LPwork\Http\Request\RequestContextStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LPwork\Http\Exception\InvalidRouteArgumentsException;

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
     * @var ErrorContextFactory
     */
    private ErrorContextFactory $errorContextFactory;

    /**
     * @var ErrorLogConfiguration
     */
    private ErrorLogConfiguration $errorLogConfiguration;

    /**
     * @param ErrorResponseBuilder    $responseBuilder
     * @param ErrorLoggerInterface    $errorLogger
     * @param ErrorIdProviderInterface $errorIdProvider
     * @param ErrorContextFactory     $errorContextFactory
     * @param ErrorLogConfiguration   $errorLogConfiguration
     */
    public function __construct(
        ErrorResponseBuilder $responseBuilder,
        ErrorLoggerInterface $errorLogger,
        ErrorIdProviderInterface $errorIdProvider,
        ErrorContextFactory $errorContextFactory,
        ErrorLogConfiguration $errorLogConfiguration,
    ) {
        $this->responseBuilder = $responseBuilder;
        $this->errorLogger = $errorLogger;
        $this->errorIdProvider = $errorIdProvider;
        $this->errorContextFactory = $errorContextFactory;
        $this->errorLogConfiguration = $errorLogConfiguration;
    }

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $this->errorIdProvider->clear();
        RequestContextStore::clear();

        try {
            $response = $handler->handle($request);

            if ($this->isErrorStatus($response->getStatusCode())) {
                return $this->handleClientErrorResponse($request, $response);
            }

            return $response;
        } catch (InvalidRouteArgumentsException $throwable) {
            return $this->renderClientException($request, $throwable);
        } catch (\Throwable $throwable) {
            $context = $this->errorContextFactory->fromRequest(
                $request,
                \bin2hex(\random_bytes(16)),
                500,
                $throwable,
            );
            $errorId = $context->id();
            $this->errorLogger->log($throwable, [
                'error_context' => $context->toArray(),
            ]);

            return $this->renderError(
                $request,
                500,
                $throwable->getMessage(),
                $throwable,
                $errorId,
                $context,
            );
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    private function handleClientErrorResponse(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $status = $response->getStatusCode();
        $reason = $response->getReasonPhrase() ?: null;

        if (!$this->shouldLogClientErrors()) {
            return $this->renderError($request, $status, $reason, null, null);
        }

        $context = $this->errorContextFactory->fromRequest(
            $request,
            \bin2hex(\random_bytes(16)),
            $status,
            null,
        );
        $errorId = $this->errorLogger->log(new \RuntimeException($reason ?? 'HTTP error'), [
            'error_context' => $context->toArray(),
        ]);

        return $this->renderError($request, $status, $reason, null, $errorId, $context);
    }

    /**
     * @param ServerRequestInterface $request
     * @param InvalidRouteArgumentsException $throwable
     *
     * @return ResponseInterface
     */
    private function renderClientException(
        ServerRequestInterface $request,
        InvalidRouteArgumentsException $throwable,
    ): ResponseInterface {
        if (!$this->shouldLogClientErrors()) {
            return $this->renderError($request, 400, $throwable->getMessage(), $throwable, null);
        }

        $context = $this->errorContextFactory->fromRequest(
            $request,
            \bin2hex(\random_bytes(16)),
            400,
            $throwable,
        );
        $errorId = $this->errorLogger->log($throwable, [
            'error_context' => $context->toArray(),
        ]);

        return $this->renderError(
            $request,
            400,
            $throwable->getMessage(),
            $throwable,
            $errorId,
            $context,
        );
    }

    /**
     * @return bool
     */
    private function shouldLogClientErrors(): bool
    {
        return $this->errorLogConfiguration->logClientErrors();
    }

    /**
     * @param ServerRequestInterface $request
     * @param int                    $status
     * @param string|null            $message
     * @param \Throwable|null        $throwable
     * @param string|null            $errorId
     * @param \LPwork\Http\Error\ErrorContext|null $context
     *
     * @return ResponseInterface
     */
    private function renderError(
        ServerRequestInterface $request,
        int $status,
        ?string $message,
        ?\Throwable $throwable,
        ?string $errorId,
        ?\LPwork\Http\Error\ErrorContext $context = null,
    ): ResponseInterface {
        $id = $errorId ?? \bin2hex(\random_bytes(16));
        $this->errorIdProvider->setCurrentErrorId($id);
        $context =
            $context ?? $this->errorContextFactory->fromRequest($request, $id, $status, $throwable);

        if ($this->wantsJson($request)) {
            return $this->responseBuilder->buildApiError(
                $request,
                $status,
                $id,
                $message,
                $throwable,
                $context,
            );
        }

        return $this->responseBuilder->buildHtmlError($request, $status, $id, $throwable, $context);
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
        $accept = $request->getHeaderLine('Accept');

        if (\stripos($accept, 'application/json') !== false) {
            return true;
        }

        $path = $request->getUri()->getPath();

        return \str_starts_with($path, '/api');
    }
}
