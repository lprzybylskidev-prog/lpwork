<?php

declare(strict_types=1);

namespace LPWork\Console;

/**
 * Represents the console bootstrap notice framework component.
 */
final readonly class ConsoleBootstrapNotice
{
    /**
     * Creates a new ConsoleBootstrapNotice instance.
     */
    public function __construct(
        public string $message,
    ) {}
}
