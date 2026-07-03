<?php

declare(strict_types=1);

namespace LPWork\Console;

use function array_fill;
use function array_map;
use function array_values;
use function count;
use function implode;

use LPWork\Console\Enums\ConsoleColor;
use LPWork\Console\Enums\ConsoleStyle;

use function max;
use function preg_replace;
use function sprintf;
use function str_repeat;
use function strlen;

/**
 * Renders console table renderer output.
 */
final class ConsoleTableRenderer
{
    /**
     * Renders this component into its output representation.
     */
    public function render(ConsoleTable $table, Output $output): void
    {
        $widths = $this->widths($table);
        $border = $this->border($widths);

        $output->writeln($border);
        $output->writeln($this->row($table->headers, $widths, $output, header: true));
        $output->writeln($border);

        foreach ($table->rows as $row) {
            $output->writeln($this->row($row, $widths, $output));
        }

        $output->writeln($border);
    }

    /**
     * @return list<int>
     */
    private function widths(ConsoleTable $table): array
    {
        $widths = array_fill(0, count($table->headers), 0);

        foreach ([$table->headers, ...$table->rows] as $row) {
            foreach ($row as $index => $value) {
                $widths[$index] = max($widths[$index], $this->visibleLength($value));
            }
        }

        return array_values($widths);
    }

    /**
     * @param list<int> $widths
     */
    private function border(array $widths): string
    {
        return '+-' . implode('-+-', array_map(
            static fn(int $width): string => str_repeat('-', $width),
            $widths,
        )) . '-+';
    }

    /**
     * @param list<string> $values
     * @param list<int> $widths
     */
    private function row(array $values, array $widths, Output $output, bool $header = false): string
    {
        $cells = [];

        foreach ($values as $index => $value) {
            $cell = $this->pad($value, $widths[$index]);

            if ($header) {
                $cell = $output->format($cell, ConsoleColor::Cyan, styles: [ConsoleStyle::Bold]);
            }

            $cells[] = $cell;
        }

        return sprintf('| %s |', implode(' | ', $cells));
    }

    private function pad(string $value, int $width): string
    {
        return $value . str_repeat(' ', max(0, $width - $this->visibleLength($value)));
    }

    private function visibleLength(string $value): int
    {
        return strlen((string) preg_replace('/\033\[[0-9;]*m/', '', $value));
    }
}
