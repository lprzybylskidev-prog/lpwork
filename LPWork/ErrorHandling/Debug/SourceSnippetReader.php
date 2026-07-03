<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Debug;

/**
 * Represents the source snippet reader framework component.
 */
final readonly class SourceSnippetReader
{
    /**
     * Creates a new SourceSnippetReader instance.
     */
    public function __construct(
        private int $radius = 8,
    ) {}

    /**
     * @return list<array{number: int, code: string, active: bool}>
     */
    public function read(?string $file, ?int $line): array
    {
        if ($file === null || $file === '' || $line === null || !is_file($file) || !is_readable($file)) {
            return [];
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);

        if (!is_array($lines)) {
            return [];
        }

        $start = max(1, $line - $this->radius);
        $end = min(count($lines), $line + $this->radius);
        $snippet = [];

        for ($number = $start; $number <= $end; $number++) {
            $snippet[] = [
                'number' => $number,
                'code' => $lines[$number - 1] ?? '',
                'active' => $number === $line,
            ];
        }

        return $snippet;
    }
}
