<?php

declare(strict_types=1);

namespace LPWork\Database\Schema;

use LPWork\Database\SqlIdentifier;

/**
 * Represents the table framework component.
 */
final class Table
{
    /**
     * @var list<ColumnDefinition>
     */
    private array $columns = [];

    /**
     * Performs the string operation.
     */
    public function string(string $name, int $length = 255): self
    {
        return $this->column($name, 'varchar(' . $length . ')');
    }

    /**
     * Performs the primary string operation.
     */
    public function primaryString(string $name, int $length = 64): self
    {
        return $this->column($name, 'varchar(' . $length . ')', primary: true);
    }

    /**
     * Performs the text operation.
     */
    public function text(string $name, bool $nullable = false): self
    {
        return $this->column($name, 'text', nullable: $nullable);
    }

    /**
     * Performs the integer operation.
     */
    public function integer(string $name): self
    {
        return $this->column($name, 'integer');
    }

    /**
     * Performs the nullable integer operation.
     */
    public function nullableInteger(string $name): self
    {
        return $this->column($name, 'integer', nullable: true);
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return array_map(
            static fn(ColumnDefinition $column): string => $column->toSql(),
            $this->columns,
        );
    }

    private function column(string $name, string $type, bool $nullable = false, bool $primary = false): self
    {
        $this->columns[] = new ColumnDefinition(SqlIdentifier::column($name), $type, $nullable, $primary);

        return $this;
    }
}
