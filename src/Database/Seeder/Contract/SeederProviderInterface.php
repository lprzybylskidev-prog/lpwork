<?php
declare(strict_types=1);

namespace LPwork\Database\Seeder\Contract;

/**
 * Provides seeders keyed by connection name.
 */
interface SeederProviderInterface
{
    /**
     * Returns seeders grouped by connection name.
     *
     * @return array<string, array<int, SeederInterface>>
     */
    public function getSeeders(): array;
}
