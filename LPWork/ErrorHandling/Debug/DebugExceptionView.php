<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Debug;

/**
 * Represents the debug exception view framework component.
 */
final readonly class DebugExceptionView
{
    /**
     * @param list<string> $nameParts
     * @param list<DebugStackFrame> $frames
     * @param array{app: int, lpwork: int, vendor: int, other: int, all: int} $frameCounts
     * @param list<DebugPreviousException> $previousExceptions
     */
    public function __construct(
        public string $name,
        public array $nameParts,
        public string $message,
        public ?int $code,
        public string $file,
        public ?int $line,
        public array $frames,
        public array $frameCounts,
        public array $previousExceptions,
    ) {}
}
