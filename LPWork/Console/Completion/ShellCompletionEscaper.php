<?php

declare(strict_types=1);

namespace LPWork\Console\Completion;

use function str_replace;

/**
 * Represents the shell completion escaper framework component.
 */
final readonly class ShellCompletionEscaper
{
    /**
     * Performs the quote operation.
     */
    public function quote(string $value): string
    {
        return "'" . str_replace("'", "'\"'\"'", $value) . "'";
    }
}
