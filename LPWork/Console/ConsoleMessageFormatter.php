<?php

declare(strict_types=1);

namespace LPWork\Console;

use LPWork\Console\Enums\ConsoleColor;
use LPWork\Console\Enums\ConsoleStyle;

/**
 * Represents the console message formatter framework component.
 */
final readonly class ConsoleMessageFormatter
{
    /**
     * Performs the title operation.
     */
    public function title(Output $output, string $title): void
    {
        $output->writelnFormatted($title, ConsoleColor::LpworkBlue, styles: [ConsoleStyle::Bold]);
        $output->writeln();
    }

    /**
     * Returns the current status value.
     */
    public function status(Output $output, string $label, string $status, bool $successful): void
    {
        $output->writeln(sprintf(
            '%s: %s',
            $label,
            $output->format($status, $successful ? ConsoleColor::Green : ConsoleColor::Red),
        ));
    }

    /**
     * Performs the success operation.
     */
    public function success(Output $output, string $message): void
    {
        $output->writeln($this->label($output, 'OK', ConsoleColor::Green) . ' ' . $message);
    }

    /**
     * Performs the warning operation.
     */
    public function warning(Output $output, string $message): void
    {
        $output->writeln($this->label($output, 'WARN', ConsoleColor::Yellow) . ' ' . $message);
    }

    /**
     * Performs the info operation.
     */
    public function info(Output $output, string $message): void
    {
        $output->writeln($this->label($output, 'INFO', ConsoleColor::Cyan) . ' ' . $message);
    }

    /**
     * Performs the muted operation.
     */
    public function muted(Output $output, string $message): void
    {
        $output->writelnFormatted($message, ConsoleColor::Gray);
    }

    /**
     * Performs the error operation.
     */
    public function error(Output $output, string $message): void
    {
        $output->error($this->label($output, 'ERROR', ConsoleColor::Red) . ' ' . $message);
    }

    /**
     * Performs the section operation.
     */
    public function section(Output $output, string $title): void
    {
        $output->writelnFormatted($title, ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);
    }

    /**
     * Performs the command operation.
     */
    public function command(Output $output, string $command): void
    {
        $output->writeln('  ' . $output->format($command, ConsoleColor::Gray));
    }

    /**
     * @param array<string, string|int|bool|null> $values
     */
    public function summary(Output $output, array $values): void
    {
        $rows = [];

        foreach ($values as $name => $value) {
            $rows[] = [$name, $this->value($value)];
        }

        new ConsoleTableRenderer()->render(ConsoleTable::make(['Metric', 'Value'], $rows), $output);
    }

    /**
     * @param non-empty-list<string> $headers
     * @param list<list<string>> $rows
     */
    public function table(Output $output, array $headers, array $rows): void
    {
        new ConsoleTableRenderer()->render(ConsoleTable::make($headers, $rows), $output);
    }

    private function label(Output $output, string $label, ConsoleColor $color): string
    {
        return $output->format($label, $color, styles: [ConsoleStyle::Bold]);
    }

    private function value(string|int|bool|null $value): string
    {
        if ($value === null) {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        return (string) $value;
    }
}
