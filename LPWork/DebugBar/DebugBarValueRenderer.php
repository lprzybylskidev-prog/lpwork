<?php

declare(strict_types=1);

namespace LPWork\DebugBar;

use LPWork\Frontend\DiagnosticValueRenderer;

/**
 * Renders debug bar value renderer output.
 */
final readonly class DebugBarValueRenderer
{
    /**
     * Creates a new DebugBarValueRenderer instance.
     */
    public function __construct(
        private DiagnosticValueRenderer $diagnostics = new DiagnosticValueRenderer(),
    ) {}

    /**
     * Returns value.
     */
    public function value(mixed $value): string
    {
        if (is_array($value)) {
            if ($value === []) {
                return $this->emptyState('No diagnostics recorded.');
            }

            if ($this->isRecordList($value)) {
                $items = '';

                foreach ($value as $item) {
                    if (is_array($item)) {
                        $items .= $this->record($item, $this->summaryParts($item));
                    }
                }

                return $this->recordList($items);
            }

            return $this->fields($value);
        }

        return $this->fields(['Value' => $value]);
    }

    /**
     * Performs the empty state operation.
     */
    public function emptyState(string $message): string
    {
        return sprintf(
            '<div class="lp-debug-data-card"><header class="lp-debug-data-head"><span>Record</span><span>Details</span></header><p class="lp-debug-empty">%s</p></div>',
            $this->escape($message),
        );
    }

    /**
     * Performs the record list operation.
     */
    public function recordList(string $items): string
    {
        return '<div class="lp-debug-data-card"><header class="lp-debug-data-head"><span>Record</span><span>Details</span></header><div class="lp-debug-records">' . $items . '</div></div>';
    }

    /**
     * @param array<string|int, mixed> $data
     * @param list<string> $summaryParts
     */
    public function record(array $data, array $summaryParts, ?string $recordId = null): string
    {
        $summary = $summaryParts === [] ? 'Details' : implode(' · ', array_filter($summaryParts, static fn(string $part): bool => $part !== ''));
        $attribute = $recordId === null ? '' : ' data-lp-debug-record="' . $this->escape($recordId) . '"';

        return sprintf(
            '<details class="lp-debug-record"%s><summary><span>%s</span><small>%s</small></summary>%s</details>',
            $attribute,
            $this->escape($summary),
            $this->escape($this->recordMeta($data)),
            $this->fields($data),
        );
    }

    /**
     * @param array<string|int, mixed> $data
     */
    public function fields(array $data): string
    {
        if ($data === []) {
            return $this->emptyState('No diagnostics recorded.');
        }

        $items = '';

        foreach ($data as $key => $value) {
            $items .= sprintf(
                '<article class="lp-debug-field"><div class="lp-debug-field-name">%s</div><div class="lp-debug-field-value">%s</div></article>',
                $this->escape((string) $key),
                $this->debugValue($value),
            );
        }

        return '<div class="lp-debug-data-card"><header class="lp-debug-data-head"><span>Field</span><span>Value</span></header><div class="lp-debug-fields">' . $items . '</div></div>';
    }

    /**
     * @param array<string|int, mixed> $data
     */
    private function recordMeta(array $data): string
    {
        $meta = [];

        foreach (['Duration ms', 'Successful', 'Status', 'Connection', 'Store', 'Queue'] as $key) {
            if (array_key_exists($key, $data) && !is_array($data[$key])) {
                $meta[] = $key . ': ' . $this->stringify($data[$key]);
            }
        }

        return implode(' · ', $meta);
    }

    /**
     * @param array<string|int, mixed> $data
     * @return list<string>
     */
    private function summaryParts(array $data): array
    {
        foreach ([
            ['Name', 'Value', 'Unit'],
            ['Status', 'Job', 'ID'],
            ['Operation', 'Store', 'Key'],
            ['event', 'listeners'],
            ['Connection', 'SQL', 'Duration ms'],
            ['Task', 'Status', 'Target'],
            ['Reason', 'Message'],
            ['Flow', 'Context'],
            ['Level', 'Message', 'Channel'],
            ['Method', 'URL'],
        ] as $keys) {
            $parts = [];

            foreach ($keys as $key) {
                if (!array_key_exists($key, $data)) {
                    continue;
                }

                if (is_array($data[$key])) {
                    $parts[] = $key . ': ' . $this->stringifySummaryArray($data[$key]);

                    continue;
                }

                $parts[] = $key . ': ' . $this->stringify($data[$key]);
            }

            if ($parts !== []) {
                return $parts;
            }
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($value !== []) {
                    return [(string) $key . ': ' . $this->stringifySummaryArray($value)];
                }

                continue;
            }

            return [(string) $key . ': ' . $this->stringify($value)];
        }

        return [];
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private function stringifySummaryArray(array $value): string
    {
        if ($value === []) {
            return 'empty';
        }

        if (array_is_list($value)) {
            return count($value) . ' item(s)';
        }

        $parts = [];

        foreach ($value as $key => $item) {
            $parts[] = is_array($item)
                ? (string) $key . '=' . count($item) . ' item(s)'
                : (string) $key . '=' . $this->stringify($item);

            if (count($parts) >= 3) {
                break;
            }
        }

        return implode(', ', $parts);
    }

    private function debugValue(mixed $value): string
    {
        if (is_array($value)) {
            return $this->diagnostics->render($value);
        }

        if (is_bool($value)) {
            return '<span class="lp-debug-token ' . ($value ? 'is-true' : 'is-false') . '">' . ($value ? 'true' : 'false') . '</span>';
        }

        if ($value === null) {
            return '<span class="lp-debug-muted">null</span>';
        }

        if ($value === '') {
            return '<span class="lp-debug-muted">empty string</span>';
        }

        if (is_int($value) || is_float($value)) {
            return '<code class="lp-debug-code is-number">' . $this->escape($this->stringify($value)) . '</code>';
        }

        if (is_scalar($value)) {
            return '<code class="lp-debug-code">' . $this->escape($value) . '</code>';
        }

        if (is_object($value)) {
            return '<code class="lp-debug-code">' . $this->escape($value::class) . '</code>';
        }

        return '<span class="lp-debug-muted">unsupported value</span>';
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private function isRecordList(array $value): bool
    {
        if (!array_is_list($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!is_array($item)) {
                return false;
            }
        }

        return true;
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_float($value)) {
            return $this->number($value);
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : get_debug_type($value);
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
