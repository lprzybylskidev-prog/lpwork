<?php

declare(strict_types=1);

namespace LPWork\Config;

use function array_map;
use function count;

use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\Enums\ConsoleColor;
use LPWork\Console\Output;

use function sprintf;

/**
 * Renders environment validation renderer output.
 */
final readonly class EnvironmentValidationRenderer
{
    /**
     * Creates a new EnvironmentValidationRenderer instance.
     */
    public function __construct(
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Renders this component into its output representation.
     */
    public function render(EnvironmentValidationReport $report, Output $output): void
    {
        $this->messages->status($output, 'Configuration', $report->status(), $report->isValid());
        $output->writeln(sprintf(
            'Summary: %d checked, %d failed',
            $report->checked,
            count($report->issues()),
        ));

        if ($report->isValid()) {
            $this->messages->success($output, 'Required environment values are present and parseable.');

            return;
        }

        $this->messages->table($output, ['Key', 'Expected', 'Status', 'Details'], array_map(
            static fn(EnvironmentValidationIssue $issue): array => [
                $issue->key,
                $issue->expectedType->value,
                $output->format('failed', ConsoleColor::Red),
                $issue->message,
            ],
            $report->issues(),
        ));
    }
}
