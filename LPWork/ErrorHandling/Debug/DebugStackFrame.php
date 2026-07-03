<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Debug;

/**
 * Represents the debug stack frame framework component.
 */
final readonly class DebugStackFrame
{
    /**
     * @param list<array{number: int, code: string, active: bool}> $sourceLines
     */
    public function __construct(
        public int $index,
        public string $label,
        public ?string $file,
        public ?int $line,
        public string $source,
        public array $sourceLines,
    ) {}

    /**
     * Performs the location operation.
     */
    public function location(): string
    {
        if ($this->file === null || $this->file === '') {
            return '[internal]';
        }

        if ($this->line === null) {
            return $this->file;
        }

        return $this->file . ':' . $this->line;
    }
}
