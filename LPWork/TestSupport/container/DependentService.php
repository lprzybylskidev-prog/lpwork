<?php

declare(strict_types=1);

namespace Tests\support\container;

final class DependentService
{
    public function __construct(
        public readonly SimpleService $service,
    ) {}
}
