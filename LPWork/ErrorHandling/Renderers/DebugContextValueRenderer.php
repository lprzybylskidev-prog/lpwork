<?php

declare(strict_types=1);

namespace LPWork\ErrorHandling\Renderers;

use LPWork\Frontend\DiagnosticValueRenderer;

/**
 * Renders debug context value renderer output.
 */
final readonly class DebugContextValueRenderer
{
    /**
     * Creates a new DebugContextValueRenderer instance.
     */
    public function __construct(
        private DiagnosticValueRenderer $renderer = new DiagnosticValueRenderer(),
    ) {}

    /**
     * Renders this component into its output representation.
     */
    public function render(mixed $value): string
    {
        return $this->renderer->render($value);
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public function table(array $data): string
    {
        return $this->renderer->table($data);
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public function records(array $records): string
    {
        return $this->renderer->records($records);
    }
}
