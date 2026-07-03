<?php

declare(strict_types=1);

namespace LPWork\Database\Schema;

use Closure;

use function implode;

use LPWork\Database\Contracts\Connection;
use LPWork\Database\SqlIdentifier;

use function sprintf;

/**
 * Represents the schema framework component.
 */
final readonly class Schema
{
    /**
     * Creates a new Schema instance.
     */
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @param Closure(Table): void $definition
     */
    public function create(string $table, Closure $definition): void
    {
        $table = SqlIdentifier::table($table);
        $blueprint = new Table();
        $definition($blueprint);

        $this->connection->statement(sprintf(
            'create table %s (%s)',
            $table,
            implode(', ', $blueprint->columns()),
        ));
    }

    /**
     * Performs the drop operation.
     */
    public function drop(string $table): void
    {
        $this->connection->statement(sprintf('drop table %s', SqlIdentifier::table($table)));
    }
}
