<?php

declare(strict_types=1);

namespace Tests\support\testing\Emitters;

use LPWork\Emitters\Contracts\Emitter;
use LPWork\Responses\Contracts\Response;
use PHPUnit\Framework\Assert;

final class CapturingEmitter implements Emitter
{
    /**
     * @var list<Response>
     */
    private array $responses = [];

    public function __construct(
        private readonly int $exitCode = 0,
    ) {}

    public function emit(Response $response): int
    {
        $this->responses[] = $response;

        return $this->exitCode;
    }

    /**
     * @return list<Response>
     */
    public function responses(): array
    {
        return $this->responses;
    }

    public function lastResponse(): ?Response
    {
        if ($this->responses === []) {
            return null;
        }

        return $this->responses[count($this->responses) - 1];
    }

    public function assertEmitted(int $count = 1): self
    {
        Assert::assertCount($count, $this->responses, 'Unexpected emitted response count.');

        return $this;
    }

    public function assertLastResponse(Response $response): self
    {
        Assert::assertSame($response, $this->lastResponse(), 'Unexpected last emitted response.');

        return $this;
    }
}
