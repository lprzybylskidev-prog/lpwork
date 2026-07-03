<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Debug;

/**
 * Reports debug previous exception failures.
 */
final readonly class DebugPreviousException
{
    /**
     * @param list<string> $nameParts
     * @param list<DebugStackFrame> $frames
     * @param array{app: int, lpwork: int, vendor: int, other: int, all: int} $frameCounts
     */
    public function __construct(
        public int $index,
        public string $name,
        public array $nameParts,
        public string $message,
        public ?int $code,
        public string $file,
        public ?int $line,
        public array $frames,
        public array $frameCounts,
    ) {}
}
