<?php

declare(strict_types=1);

namespace LPWork\Console;

use function count;

use LPWork\Console\Exceptions\InvalidConsoleTableException;

/**
 * Represents the console table framework component.
 */
final readonly class ConsoleTable
{
    /**
     * @param non-empty-list<string> $headers
     * @param list<list<string>> $rows
     */
    private function __construct(
        public array $headers,
        public array $rows,
    ) {}

    /**
     * @param list<string> $headers
     * @param list<list<string>> $rows
     */
    public static function make(array $headers, array $rows): self
    {
        if ($headers === []) {
            throw InvalidConsoleTableException::emptyHeaders();
        }

        $columnCount = count($headers);

        foreach ($rows as $index => $row) {
            if (count($row) !== $columnCount) {
                throw InvalidConsoleTableException::rowColumnCountMismatch($index, $columnCount, count($row));
            }
        }

        return new self($headers, $rows);
    }
}
