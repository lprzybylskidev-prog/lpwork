<?php

declare(strict_types=1);

namespace Tests\support\queue;

use Tests\support\exceptions\TestSupportException;

final class QueueTableRows
{
    /**
     * @param list<array<string, mixed>|object> $rows
     * @return array<string, mixed>
     */
    public static function firstArray(array $rows): array
    {
        $row = $rows[0] ?? null;

        if (!is_array($row)) {
            throw TestSupportException::expectedQueueRowArray();
        }

        return $row;
    }
}
