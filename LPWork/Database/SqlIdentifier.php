<?php

declare(strict_types=1);

namespace LPWork\Database;

use LPWork\Database\Exceptions\InvalidSqlIdentifierException;

/**
 * Represents the sql identifier framework component.
 */
final readonly class SqlIdentifier
{
    /**
     * Performs the table operation.
     */
    public static function table(string $name): string
    {
        return self::validate($name);
    }

    /**
     * Performs the column operation.
     */
    public static function column(string $name): string
    {
        return self::validate($name);
    }

    private static function validate(string $name): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
            throw new InvalidSqlIdentifierException($name);
        }

        return $name;
    }
}
