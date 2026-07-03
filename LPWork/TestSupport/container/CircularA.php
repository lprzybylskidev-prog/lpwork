<?php

declare(strict_types=1);

namespace Tests\support\container;

final class CircularA
{
    public function __construct(
        public readonly CircularB $dependency,
    ) {}
}
