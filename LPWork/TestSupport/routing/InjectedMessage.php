<?php

declare(strict_types=1);

namespace Tests\support\routing;

final readonly class InjectedMessage
{
    public function __construct(
        private string $message = 'container',
    ) {}

    public function value(): string
    {
        return $this->message;
    }
}
