<?php

declare(strict_types=1);

namespace Tests\support\container;

final class CircularB
{
    public function __construct(
        public readonly CircularA $dependency,
    ) {}
}
