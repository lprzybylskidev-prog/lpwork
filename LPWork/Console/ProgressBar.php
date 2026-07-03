<?php

declare(strict_types=1);

namespace LPWork\Console;

use LPWork\Console\Exceptions\InvalidProgressBarException;

use function max;
use function min;
use function sprintf;
use function str_repeat;

/**
 * Represents the progress bar framework component.
 */
final class ProgressBar
{
    private int $current = 0;

    /**
     * Creates a new ProgressBar instance.
     */
    public function __construct(
        private readonly Output $output,
        private readonly int $total,
        private readonly int $width = 40,
    ) {
        if ($this->total < 0) {
            throw InvalidProgressBarException::negativeTotal();
        }

        if ($this->width < 1) {
            throw InvalidProgressBarException::invalidWidth();
        }
    }

    /**
     * Performs the start operation.
     */
    public function start(): void
    {
        $this->current = 0;
        $this->render();
    }

    /**
     * Performs the advance operation.
     */
    public function advance(int $step = 1): void
    {
        $this->current = min($this->total, max(0, $this->current + $step));
        $this->render();
    }

    /**
     * Performs the finish operation.
     */
    public function finish(): void
    {
        $this->current = $this->total;
        $this->render();
        $this->output->writeln();
    }

    private function render(): void
    {
        $percentage = $this->percentage();
        $filled = (int) (($percentage / 100) * $this->width);
        $empty = $this->width - $filled;

        $this->output->write(sprintf(
            "\r[%s%s] %3d%% %d/%d",
            str_repeat('=', $filled),
            str_repeat(' ', $empty),
            $percentage,
            $this->current,
            $this->total,
        ));
    }

    private function percentage(): int
    {
        if ($this->total === 0) {
            return 100;
        }

        return (int) (($this->current / $this->total) * 100);
    }
}
