<?php
declare(strict_types=1);

namespace Config;

use LPwork\Database\Seeder\Contract\SeederInterface;
use LPwork\Database\Seeder\Contract\SeederProviderInterface;

/**
 * Application-level seeder provider.
 */
class SeederProvider implements SeederProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getSeeders(): array
    {
        /** @var array<int, SeederInterface> $defaultSeeders */
        $defaultSeeders = [];

        return [
            "default" => $defaultSeeders,
            // "other_connection" => [...],
        ];
    }
}
