<?php
declare(strict_types=1);

namespace LPwork\Database\Seeder;

use LPwork\Database\Seeder\Contract\SeederProviderInterface;

/**
 * Provides framework seeders grouped by connection.
 */
class FrameworkSeederProvider implements SeederProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getSeeders(): array
    {
        return [
            'default' => [],
        ];
    }
}
