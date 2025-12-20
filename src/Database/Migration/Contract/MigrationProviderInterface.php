<?php
declare(strict_types=1);

namespace LPwork\Database\Migration\Contract;

/**
 * Provides migration paths keyed by connection name.
 */
interface MigrationProviderInterface
{
    /**
     * Returns migration paths grouped by connection name.
     *
     * @return array<string, array<int, string>>
     */
    public function getMigrationPaths(): array;
}
