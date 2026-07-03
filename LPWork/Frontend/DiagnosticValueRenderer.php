<?php

declare(strict_types=1);

namespace LPWork\Frontend;

use Stringable;

/**
 * Renders diagnostic value renderer output.
 */
final readonly class DiagnosticValueRenderer
{
    /**
     * Renders this component into its output representation.
     */
    public function render(mixed $value): string
    {
        if (is_array($value)) {
            return $this->array($value);
        }

        if (is_bool($value)) {
            return '<span class="lp-debug-token ' . ($value ? 'is-true' : 'is-false') . '">'
                . ($value ? 'true' : 'false')
                . '</span>';
        }

        if ($value === null) {
            return '<span class="lp-debug-muted">null</span>';
        }

        if ($value === '') {
            return '<span class="lp-debug-muted">empty string</span>';
        }

        if (is_int($value) || is_float($value)) {
            return '<code class="lp-debug-code is-number">' . $this->escape((string) $value) . '</code>';
        }

        if (is_string($value)) {
            return '<code class="lp-debug-code">' . $this->escape($value) . '</code>';
        }

        if ($value instanceof Stringable) {
            return '<code class="lp-debug-code">' . $this->escape((string) $value) . '</code>';
        }

        if (is_object($value)) {
            return '<code class="lp-debug-code">' . $this->escape($value::class) . '</code>';
        }

        return '<span class="lp-debug-muted">unsupported value</span>';
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public function table(array $data): string
    {
        return $this->fields($data);
    }

    /**
     * @param array<string|int, mixed> $data
     */
    public function fields(array $data): string
    {
        if ($data === []) {
            return $this->emptyState();
        }

        $items = '';

        foreach ($data as $key => $value) {
            $items .= sprintf(
                '<article class="lp-debug-field"><div class="lp-debug-field-name">%s</div><div class="lp-debug-field-value">%s</div></article>',
                $this->escape((string) $key),
                is_array($value) ? $this->array($value) : $this->render($value),
            );
        }

        return '<div class="lp-debug-data-card"><header class="lp-debug-data-head"><span>Field</span><span>Value</span></header><div class="lp-debug-fields">' . $items . '</div></div>';
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public function records(array $records): string
    {
        if ($records === []) {
            return $this->emptyState();
        }

        $html = '<div class="lp-debug-data-card"><header class="lp-debug-data-head"><span>Record</span><span>Details</span></header><div class="lp-debug-records">';

        foreach ($records as $record) {
            $html .= sprintf(
                '<details class="lp-debug-record"><summary><span>%s</span><small>%s</small></summary>%s</details>',
                $this->escape($this->recordSummary($record)),
                $this->escape($this->recordMeta($record)),
                $this->fields($record),
            );
        }

        return $html . '</div></div>';
    }

    private function emptyState(): string
    {
        return '<div class="lp-debug-data-card"><header class="lp-debug-data-head"><span>Diagnostics</span><span>Status</span></header><p class="lp-debug-empty">No diagnostics recorded.</p></div>';
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private function array(array $value): string
    {
        if ($value === []) {
            return '<span class="lp-debug-muted">empty</span>';
        }

        return '<div class="lp-debug-tree">' . $this->treeNode(null, $value) . '</div>';
    }

    private function treeNode(string|int|null $key, mixed $value): string
    {
        if (is_array($value) && $value !== []) {
            $html = sprintf(
                '<details class="lp-debug-tree-node is-branch"><summary>%s</summary><div class="lp-debug-tree-children">',
                $this->treeRow($key, $this->arraySummary($value), 'array', 'items: ' . count($value), true),
            );

            foreach ($value as $childKey => $child) {
                $html .= $this->treeNode($childKey, $child);
            }

            return $html . '</div></details>';
        }

        return '<div class="lp-debug-tree-node is-leaf">' . $this->treeRow(
            $key,
            $this->valueSummary($value),
            $this->valueType($value),
            $this->valueMeta($value),
            false,
        ) . '</div>';
    }

    private function treeRow(string|int|null $key, string $summary, string $type, string $meta, bool $branch): string
    {
        $keyHtml = $key === null
            ? '<span class="lp-debug-tree-key is-root">root</span>'
            : '<span class="lp-debug-tree-key">' . $this->escape((string) $key) . '</span>';
        $summaryHtml = $branch
            ? '<span class="lp-debug-tree-summary">' . $this->escape($summary) . '</span>'
            : '<code class="lp-debug-code">' . $this->escape($summary) . '</code>';

        return '<span class="lp-debug-tree-row">'
            . ($branch ? '<span class="lp-debug-tree-toggle" aria-hidden="true"></span>' : '<span class="lp-debug-tree-spacer" aria-hidden="true"></span>')
            . $keyHtml
            . '<span class="lp-debug-tree-value">'
            . $summaryHtml
            . '<span class="lp-debug-tree-details"><span class="lp-debug-token">' . $this->escape($type) . '</span>'
            . ($meta === '' ? '' : '<span class="lp-debug-tree-meta">' . $this->escape($meta) . '</span>')
            . '</span></span></span>';
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private function arraySummary(array $value): string
    {
        return 'array(' . count($value) . ')';
    }

    private function valueSummary(mixed $value): string
    {
        if (is_array($value)) {
            return $this->arraySummary($value);
        }

        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === '') {
            return 'empty string';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_object($value)) {
            return $value::class;
        }

        return 'unsupported value';
    }

    private function valueType(mixed $value): string
    {
        return get_debug_type($value);
    }

    private function valueMeta(mixed $value): string
    {
        if (is_array($value)) {
            return 'items: ' . count($value);
        }

        if (is_string($value) || $value instanceof Stringable) {
            return 'length: ' . strlen((string) $value);
        }

        return '';
    }

    /**
     * @param array<array-key, mixed> $record
     */
    private function recordSummary(array $record): string
    {
        foreach (['Message', 'Name', 'SQL', 'Operation', 'Task', 'Job', 'URL'] as $key) {
            if (array_key_exists($key, $record) && !is_array($record[$key])) {
                return $this->summaryValue($record[$key]);
            }
        }

        foreach ($record as $key => $value) {
            if (!is_array($value)) {
                return $key . ': ' . $this->summaryValue($value);
            }
        }

        return 'Details';
    }

    /**
     * @param array<array-key, mixed> $record
     */
    private function recordMeta(array $record): string
    {
        $meta = [];

        foreach (['Duration ms', 'Successful', 'Status', 'Connection', 'Store', 'Queue', 'Unit'] as $key) {
            if (array_key_exists($key, $record) && !is_array($record[$key])) {
                $meta[] = $key . ': ' . $this->summaryValue($record[$key]);
            }
        }

        return $meta === [] ? count($record) . ' fields' : implode(' · ', $meta);
    }

    private function summaryValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return get_debug_type($value);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
