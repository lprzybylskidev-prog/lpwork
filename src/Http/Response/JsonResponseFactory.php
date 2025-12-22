<?php
declare(strict_types=1);

namespace LPwork\Http\Response;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Builds JSON responses using PSR-17 factories.
 */
class JsonResponseFactory
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
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface   $streamFactory
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * Creates a JSON response.
     *
     * @param mixed $data
     * @param int   $status
     * @param array<string, string> $headers
     *
     * @return ResponseInterface
     */
    public function json(mixed $data, int $status = 200, array $headers = []): ResponseInterface
    {
        $payload = \json_encode($data, \JSON_THROW_ON_ERROR);
        $body = $this->streamFactory->createStream($payload);

        $response = $this->responseFactory
            ->createResponse($status)
            ->withHeader('Content-Type', 'application/json');

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response->withBody($body);
    }

    /**
     * Creates an empty response with status code.
     *
     * @param int $status
     *
     * @return ResponseInterface
     */
    public function empty(int $status = 204): ResponseInterface
    {
        return $this->responseFactory->createResponse($status);
    }
}
