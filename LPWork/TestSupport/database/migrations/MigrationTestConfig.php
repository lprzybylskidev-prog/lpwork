<?php

declare(strict_types=1);

namespace Tests\support\database\migrations;

use LPWork\Config\Config;
use LPWork\Config\Contracts\ConfigDefinition;

final readonly class MigrationTestConfig
{
    public static function init(string $environment = 'testing'): void
    {
        Config::reset();
        Config::initDefinitions([
            new readonly class ($environment) implements ConfigDefinition {
                public function __construct(private string $environment) {}

                public function key(): string
                {
                    return 'app';
                }

                public function values(): array
                {
                    return [
                        'env' => $this->environment,
                    ];
                }
            },
        ]);
    }
}
