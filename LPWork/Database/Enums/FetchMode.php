<?php

declare(strict_types=1);

namespace LPWork\Database\Enums;

use PDO;

/**
 * Enumerates the supported fetch mode values.
 */
enum FetchMode
{
    case Associative;
    case Object;

    /**
     * Performs the pdo mode operation.
     */
    public function pdoMode(): int
    {
        return match ($this) {
            self::Associative => PDO::FETCH_ASSOC,
            self::Object => PDO::FETCH_OBJ,
        };
    }
}
