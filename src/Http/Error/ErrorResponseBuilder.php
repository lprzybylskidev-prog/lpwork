<?php
declare(strict_types=1);

namespace LPwork\Http\Error;

use LPwork\Config\Contract\ConfigRepositoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

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
     * @param ConfigRepositoryInterface $config
     * @param Psr17Factory              $psr17Factory
     */
    public function __construct(
        ConfigRepositoryInterface $config,
        Psr17Factory $psr17Factory,
    ) {
        $this->config = $config;
        $this->psr17Factory = $psr17Factory;
    }

    /**
     * @param ServerRequestInterface    $request
     * @param int                       $status
     * @param string                    $errorId
     * @param string|null               $message
     * @param \Throwable|null           $throwable
     *
     * @return ResponseInterface
     */
    public function buildApiError(
        ServerRequestInterface $request,
        int $status,
        string $errorId,
        ?string $message = null,
        ?\Throwable $throwable = null,
    ): ResponseInterface {
        $isDev = $this->isDev();
        $body = [
            "code" => $status,
            "error_id" => $errorId,
        ];

        if ($isDev) {
            $body["message"] = $message ?? "Error";
            $body["trace"] =
                $throwable !== null ? $throwable->getTraceAsString() : null;
        }

        $json = \json_encode($body, \JSON_THROW_ON_ERROR);

        $response = $this->psr17Factory
            ->createResponse($status)
            ->withHeader("Content-Type", "application/json");

        return $response->withBody($this->psr17Factory->createStream($json));
    }

    /**
     * @param ServerRequestInterface $request
     * @param int                    $status
     * @param string                 $errorId
     * @param \Throwable|null        $throwable
     *
     * @return ResponseInterface
     */
    public function buildHtmlError(
        ServerRequestInterface $request,
        int $status,
        string $errorId,
        ?\Throwable $throwable = null,
    ): ResponseInterface {
        if ($this->isDev()) {
            $whoops = new Run();
            $whoops->pushHandler(new PrettyPageHandler());

            $html = $whoops->handleException(
                $throwable ?? new \RuntimeException("HTTP error", $status),
            );

            return $this->psr17Factory
                ->createResponse($status)
                ->withHeader("Content-Type", "text/html")
                ->withBody($this->psr17Factory->createStream($html));
        }

        $html = \sprintf(
            "<h1>Error</h1><p>Code: %d</p><p>Error ID: %s</p>",
            $status,
            $errorId,
        );

        return $this->psr17Factory
            ->createResponse($status)
            ->withHeader("Content-Type", "text/html")
            ->withBody($this->psr17Factory->createStream($html));
    }

    /**
     * @return bool
     */
    private function isDev(): bool
    {
        return $this->config->getString("app.env", "prod") === "dev";
    }
}
