<?php
declare(strict_types=1);

namespace LPwork\Http\Error;

use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Http\Error\Contract\DevErrorPageRendererInterface;
use LPwork\Http\Error\ErrorContext;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Builds error responses for API and HTML contexts.
 */
class ErrorResponseBuilder
{
    /**
     * @var ConfigRepositoryInterface
     */
    private ConfigRepositoryInterface $config;

    /**
     * @var Psr17Factory
     */
    private Psr17Factory $psr17Factory;

    /**
     * @var DevErrorPageRendererInterface
     */
    private DevErrorPageRendererInterface $devErrorPageRenderer;

    /**
     * @param ConfigRepositoryInterface $config
     * @param Psr17Factory              $psr17Factory
     * @param DevErrorPageRendererInterface $devErrorPageRenderer
     */
    public function __construct(
        ConfigRepositoryInterface $config,
        Psr17Factory $psr17Factory,
        DevErrorPageRendererInterface $devErrorPageRenderer,
    ) {
        $this->config = $config;
        $this->psr17Factory = $psr17Factory;
        $this->devErrorPageRenderer = $devErrorPageRenderer;
    }

    /**
     * @param ServerRequestInterface    $request
     * @param int                       $status
     * @param string                    $errorId
     * @param string|null               $message
     * @param \Throwable|null           $throwable
     * @param ErrorContext|null         $context
     *
     * @return ResponseInterface
     */
    public function buildApiError(
        ServerRequestInterface $request,
        int $status,
        string $errorId,
        ?string $message = null,
        ?\Throwable $throwable = null,
        ?ErrorContext $context = null,
    ): ResponseInterface {
        $isDev = $this->isDev();
        $body = [
            'code' => $status,
        ];

        if ($this->shouldExposeApiErrorId()) {
            $body['error_id'] = $errorId;
        }

        if ($isDev) {
            $body['message'] = $message ?? 'Error';
            if ($context !== null) {
                $body['context'] = $context->toArray();
            } else {
                $body['trace'] = $throwable !== null ? $throwable->getTraceAsString() : null;
            }
        }

        $json = \json_encode($body, \JSON_THROW_ON_ERROR);

        $response = $this->psr17Factory->createResponse($status);
        $response = $this->applyErrorIdHeader($response, $errorId);
        $response = $response->withHeader('Content-Type', 'application/json');

        return $response->withBody($this->psr17Factory->createStream($json));
    }

    /**
     * @param ServerRequestInterface $request
     * @param int                    $status
     * @param string                 $errorId
     * @param \Throwable|null        $throwable
     * @param ErrorContext|null      $context
     *
     * @return ResponseInterface
     */
    public function buildHtmlError(
        ServerRequestInterface $request,
        int $status,
        string $errorId,
        ?\Throwable $throwable = null,
        ?ErrorContext $context = null,
    ): ResponseInterface {
        if ($this->isDev()) {
            $html = $this->devErrorPageRenderer->render(
                $request,
                $status,
                $errorId,
                $throwable ?? new \RuntimeException('HTTP error', $status),
                $context,
            );

            $response = $this->psr17Factory->createResponse($status);
            $response = $this->applyErrorIdHeader($response, $errorId);

            return $response
                ->withHeader('Content-Type', 'text/html')
                ->withBody($this->psr17Factory->createStream($html));
        }

        $html = \sprintf('<h1>Error</h1><p>Code: %d</p><p>Error ID: %s</p>', $status, $errorId);

        $response = $this->psr17Factory->createResponse($status);
        $response = $this->applyErrorIdHeader($response, $errorId);

        return $response
            ->withHeader('Content-Type', 'text/html')
            ->withBody($this->psr17Factory->createStream($html));
    }

    /**
     * @return bool
     */
    private function isDev(): bool
    {
        return $this->config->getString('app.env', 'prod') === 'dev';
    }

    /**
     * @return bool
     */
    private function shouldExposeHeader(): bool
    {
        return (bool) $this->config->getBool('error_log.response.expose_header', true);
    }

    /**
     * @return string
     */
    private function headerName(): string
    {
        return $this->config->getString('error_log.response.header_name', 'X-Error-Id');
    }

    /**
     * @return bool
     */
    private function shouldExposeApiErrorId(): bool
    {
        return (bool) $this->config->getBool('error_log.response.expose_api_payload', true);
    }

    /**
     * @param ResponseInterface $response
     * @param string            $errorId
     *
     * @return ResponseInterface
     */
    private function applyErrorIdHeader(
        ResponseInterface $response,
        string $errorId,
    ): ResponseInterface {
        if (!$this->shouldExposeHeader()) {
            return $response;
        }

        return $response->withHeader($this->headerName(), $errorId);
    }
}
