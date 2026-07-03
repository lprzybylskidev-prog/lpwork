<?php

declare(strict_types=1);

namespace LPWork\Emitters;

use Closure;
use LPWork\Emitters\Contracts\Emitter;
use LPWork\Emitters\Exceptions\HttpOutputOpenException;
use LPWork\Emitters\Exceptions\ResponseOutputWriteException;
use LPWork\Emitters\Exceptions\UnsupportedResponseException;
use LPWork\Responses\Contracts\Response;
use LPWork\Responses\HttpResponse;

/**
 * Represents the http emitter framework component.
 */
final readonly class HttpEmitter implements Emitter
{
    /**
     * @param resource $output
     * @param Closure(string): void $headerSender
     * @param Closure(int): void $statusSender
     */
    public function __construct(
        private mixed $output,
        private Closure $headerSender,
        private Closure $statusSender,
    ) {}

    /**
     * Performs the browser operation.
     */
    public static function browser(): self
    {
        $output = fopen('php://output', 'wb');

        if ($output === false) {
            throw new HttpOutputOpenException();
        }

        return new self(
            output: $output,
            headerSender: static function (string $header): void {
                header($header, false);
            },
            statusSender: static function (int $statusCode): void {
                http_response_code($statusCode);
            },
        );
    }

    /**
     * Runs emit.
     */
    public function emit(Response $response): int
    {
        if (!$response instanceof HttpResponse) {
            throw UnsupportedResponseException::forHttpEmitter($response);
        }

        ($this->statusSender)($response->statusCode());

        foreach ($response->headers() as $name => $value) {
            ($this->headerSender)(sprintf('%s: %s', $name, $value));
        }

        foreach ($response->cookies() as $cookie) {
            ($this->headerSender)('Set-Cookie: ' . $cookie->toHeader());
        }

        $stream = $response->streamCallback();

        if ($stream !== null) {
            $stream($this->output);

            return $response->statusCode();
        }

        if (fwrite($this->output, $response->body()) === false) {
            throw new ResponseOutputWriteException();
        }

        return $response->statusCode();
    }
}
