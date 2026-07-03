<?php

declare(strict_types=1);

namespace LPWork\Database\Schema;

/**
 * Represents the column definition framework component.
 */
final readonly class ColumnDefinition
{
    /**
     * Creates a new ColumnDefinition instance.
     */
    public function __construct(
        private string $name,
        private string $type,
        private bool $nullable = false,
        private bool $primary = false,
    ) {}

    /**
     * Converts this value to to sql output.
     */
    public function toSql(): string
    {
        $sql = $this->name . ' ' . $this->type;

        if ($this->primary) {
            return $sql . ' primary key';
        }

        return $sql . ($this->nullable ? ' null' : ' not null');
    }
}
