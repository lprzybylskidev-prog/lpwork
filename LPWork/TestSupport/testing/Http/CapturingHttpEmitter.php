<?php

declare(strict_types=1);

namespace Tests\support\testing\Http;

use LPWork\Emitters\Contracts\Emitter;
use LPWork\Responses\Contracts\Response;
use LPWork\Responses\HttpResponse;
use Tests\support\exceptions\TestSupportException;

final class CapturingHttpEmitter implements Emitter
{
    public ?HttpResponse $response = null;

    public int $calls = 0;

    public function __construct(
        private readonly bool $failFirstEmit = false,
    ) {}

    public function emit(Response $response): int
    {
        $this->calls++;

        if ($this->failFirstEmit && $this->calls === 1) {
            throw TestSupportException::forcedHttpEmitFailure();
        }

        if (!$response instanceof HttpResponse) {
            throw TestSupportException::expectedHttpResponse($response::class);
        }

        $this->response = $response;

        return $response->statusCode();
    }
}
