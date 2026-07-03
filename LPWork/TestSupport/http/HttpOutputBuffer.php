<?php

declare(strict_types=1);

namespace Tests\support\http;

use LPWork\Emitters\HttpEmitter;
use Tests\support\exceptions\TestSupportException;

final class HttpOutputBuffer
{
    /**
     * @var resource
     */
    private mixed $output;

    /**
     * @var list<string>
     */
    private array $headers = [];

    private ?int $statusCode = null;

    private function __construct()
    {
        $output = fopen('php://memory', 'r+');

        if ($output === false) {
            throw TestSupportException::memoryStreamCouldNotBeOpened();
        }

        $this->output = $output;
    }

    public static function create(): self
    {
        return new self();
    }

    public function emitter(): HttpEmitter
    {
        return new HttpEmitter(
            output: $this->output,
            headerSender: function (string $header): void {
                $this->headers[] = $header;
            },
            statusSender: function (int $statusCode): void {
                $this->statusCode = $statusCode;
            },
        );
    }

    public function body(): string
    {
        rewind($this->output);

        $contents = stream_get_contents($this->output);

        return is_string($contents) ? $contents : '';
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }
}
