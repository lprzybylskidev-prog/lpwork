<?php

declare(strict_types=1);

namespace App\Modules\Welcome\Configs;

use LPWork\Config\Contracts\ConfigDefinition;

final class WelcomeConfig implements ConfigDefinition
{
    public function key(): string
    {
        return 'welcome';
    }

    /**
     * @return array<array-key, mixed>
     */
    public function values(): array
    {
        return [
            'app' => [
                'name' => 'Welcome',
            ],
        ];
    }
}
