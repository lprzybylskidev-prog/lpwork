<?php
declare(strict_types=1);

namespace LPwork\Http\Response;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Convenience response builder for common payload types.
 */
class ResponseFactory
{
    /**
     * @var ResponseFactoryInterface
     */
    private ResponseFactoryInterface $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    private StreamFactoryInterface $streamFactory;

    /**
     * @var JsonResponseFactory
     */
    private JsonResponseFactory $jsonResponseFactory;

    /**
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface   $streamFactory
     * @param JsonResponseFactory      $jsonResponseFactory
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        JsonResponseFactory $jsonResponseFactory,
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->jsonResponseFactory = $jsonResponseFactory;
    }

    /**
     * Builds a JSON response.
     *
     * @param mixed               $data
     * @param int                 $status
     * @param array<string, mixed> $headers
     *
     * @return ResponseInterface
     */
    public function json(mixed $data, int $status = 200, array $headers = []): ResponseInterface
    {
        return $this->jsonResponseFactory->json($data, $status, $headers);
    }

    /**
     * Builds a plain text response.
     *
     * @param string              $content
     * @param int                 $status
     * @param array<string, mixed> $headers
     *
     * @return ResponseInterface
     */
    public function text(string $content, int $status = 200, array $headers = []): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $body = $this->streamFactory->createStream($content);

        $response = $response->withHeader('Content-Type', 'text/plain; charset=utf-8');

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, (string) $value);
        }

        return $response->withBody($body);
    }

    /**
     * Builds an HTML response.
     *
     * @param string              $content
     * @param int                 $status
     * @param array<string, mixed> $headers
     *
     * @return ResponseInterface
     */
    public function html(string $content, int $status = 200, array $headers = []): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $body = $this->streamFactory->createStream($content);

        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, (string) $value);
        }

        return $response->withBody($body);
    }

    /**
     * Builds a response streaming a file from disk.
     *
     * @param string              $path
     * @param string|null         $downloadName
     * @param string|null         $contentType
     * @param int                 $status
     * @param array<string, mixed> $headers
     *
     * @return ResponseInterface
     */
    public function file(
        string $path,
        ?string $downloadName = null,
        ?string $contentType = null,
        int $status = 200,
        array $headers = [],
    ): ResponseInterface {
        $stream = $this->streamFactory->createStreamFromFile($path, 'r');

        $response = $this->responseFactory->createResponse($status)->withBody($stream);

        if ($contentType !== null) {
            $response = $response->withHeader('Content-Type', $contentType);
        }

        if ($downloadName !== null) {
            $response = $response->withHeader(
                'Content-Disposition',
                \sprintf('attachment; filename="%s"', $downloadName),
            );
        }

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, (string) $value);
        }

        return $response;
    }

    /**
     * Builds a response from an existing stream.
     *
     * @param StreamInterface     $stream
     * @param string|null         $contentType
     * @param int                 $status
     * @param array<string, mixed> $headers
     *
     * @return ResponseInterface
     */
    public function stream(
        StreamInterface $stream,
        ?string $contentType = null,
        int $status = 200,
        array $headers = [],
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse($status)->withBody($stream);

        if ($contentType !== null) {
            $response = $response->withHeader('Content-Type', $contentType);
        }

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, (string) $value);
        }

        return $response;
    }
}
