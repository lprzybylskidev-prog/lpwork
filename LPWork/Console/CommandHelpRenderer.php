<?php

declare(strict_types=1);

namespace LPWork\Console;

use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Enums\ConsoleColor;
use LPWork\Console\Enums\ConsoleStyle;

use function sprintf;
use function str_pad;
use function strlen;

/**
 * Renders command help renderer output.
 */
final class CommandHelpRenderer
{
    /**
     * Renders this component into its output representation.
     */
    public function render(Command $command, Output $output): void
    {
        $output->writelnFormatted($command->name(), ConsoleColor::Cyan, styles: [ConsoleStyle::Bold]);
        $output->writeln($command->description());
        $output->writeln();
        $this->renderUsage($command, $output);
        $this->renderArguments($command, $output);
        $this->renderOptions($command, $output);
    }

    private function renderUsage(Command $command, Output $output): void
    {
        $usage = '  lpwork ' . $command->name();

        if ($command instanceof DescribesInput) {
            foreach ($command->arguments() as $argument) {
                $usage .= ' ' . $argument->usage();
            }
        }

        $output->writelnFormatted('Usage:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);
        $output->writeln($usage . ' [options]');
        $output->writeln();
    }

    private function renderArguments(Command $command, Output $output): void
    {
        if (!$command instanceof DescribesInput || $command->arguments() === []) {
            return;
        }

        $output->writelnFormatted('Arguments:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);

        foreach ($command->arguments() as $argument) {
            $output->writeln(sprintf(
                '  %s  %s',
                str_pad($argument->name(), $this->longestArgumentNameLength($command)),
                $argument->description(),
            ));
        }

        $output->writeln();
    }

    private function renderOptions(Command $command, Output $output): void
    {
        $options = [
            ConsoleOption::flag('help', 'h', 'Display help for the command.'),
        ];

        if ($command instanceof DescribesInput) {
            $options = [...$options, ...$command->options()];
        }

        $output->writelnFormatted('Options:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);

        foreach ($options as $option) {
            $output->writeln(sprintf(
                '  %s  %s',
                str_pad($option->signature(), $this->longestOptionSignatureLength($options)),
                $option->description(),
            ));
        }
    }

    private function longestArgumentNameLength(DescribesInput $command): int
    {
        $length = 0;

        foreach ($command->arguments() as $argument) {
            $length = max($length, strlen($argument->name()));
        }

        return $length;
    }

    /**
     * @param list<ConsoleOption> $options
     */
    private function longestOptionSignatureLength(array $options): int
    {
        $length = 0;

        foreach ($options as $option) {
            $length = max($length, strlen($option->signature()));
        }

        return $length;
    }
}
