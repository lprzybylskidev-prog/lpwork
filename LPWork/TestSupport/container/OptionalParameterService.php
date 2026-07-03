<?php

declare(strict_types=1);

namespace Tests\support\container;

final class OptionalParameterService
{
    public function __construct(
        public readonly string $name = 'default',
    ) {}
}
