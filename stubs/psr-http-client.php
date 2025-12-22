<?php
declare(strict_types=1);

namespace Psr\Http\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

if (!\interface_exists(ClientInterface::class)) {
    /**
     * @psalm-suppress MissingReturnType
     */
    interface ClientInterface
    {
        /**
         * Sends a PSR-7 request and returns a PSR-7 response.
         *
         * @param RequestInterface $request
         *
         * @return ResponseInterface
         */
        public function sendRequest(RequestInterface $request): ResponseInterface;
    }
}

if (!\interface_exists(ClientExceptionInterface::class)) {
    /**
     * Base exception interface for HTTP clients.
     */
    interface ClientExceptionInterface extends \Throwable {}
}

if (!\interface_exists(RequestExceptionInterface::class)) {
    /**
     * Thrown when a request cannot be sent or is invalid.
     */
    interface RequestExceptionInterface extends ClientExceptionInterface
    {
        /**
         * @return RequestInterface
         */
        public function getRequest(): RequestInterface;
    }
}

if (!\interface_exists(NetworkExceptionInterface::class)) {
    /**
     * Thrown when a network error occurs.
     */
    interface NetworkExceptionInterface extends ClientExceptionInterface
    {
        /**
         * @return RequestInterface
         */
        public function getRequest(): RequestInterface;
    }
}
