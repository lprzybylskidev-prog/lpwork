<?php

declare(strict_types=1);

namespace Tests\support\container;

final class MultiDependencyService
{
    public function __construct(
        public readonly SimpleService $first,
        public readonly BoundService $second,
    ) {}
}
