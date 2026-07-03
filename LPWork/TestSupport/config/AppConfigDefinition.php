<?php

declare(strict_types=1);

namespace Tests\support\config;

use LPWork\Config\Contracts\ConfigDefinition;

final class AppConfigDefinition implements ConfigDefinition
{
    public function __construct(private readonly string $name = 'LPWork') {}

    public function key(): string
    {
        return 'app';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
