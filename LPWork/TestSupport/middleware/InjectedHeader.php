<?php

declare(strict_types=1);

namespace Tests\support\middleware;

final readonly class InjectedHeader
{
    public function __construct(
        private string $value = 'container',
    ) {}

    public function value(): string
    {
        return $this->value;
    }
}
