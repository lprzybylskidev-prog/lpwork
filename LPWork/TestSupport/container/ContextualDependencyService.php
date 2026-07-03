<?php

declare(strict_types=1);

namespace Tests\support\container;

final class ContextualDependencyService
{
    public function __construct(
        public readonly ServiceContract $service,
    ) {}
}
