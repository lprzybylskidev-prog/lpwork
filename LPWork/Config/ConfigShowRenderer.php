<?php

declare(strict_types=1);

namespace LPWork\Config;

use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

use LPWork\Console\ConsoleTable;
use LPWork\Console\ConsoleTableRenderer;
use LPWork\Console\Enums\ConsoleColor;
use LPWork\Console\Enums\ConsoleStyle;
use LPWork\Console\Output;

use function preg_match;
use function var_export;

/**
 * Renders config show renderer output.
 */
final class ConfigShowRenderer
{
    /**
     * Creates a new ConfigShowRenderer instance.
     */
    public function __construct(
        private readonly ConsoleTableRenderer $tables = new ConsoleTableRenderer(),
    ) {}

    /**
     * @param array<array-key, mixed> $config
     */
    public function render(array $config, Output $output, bool $showSecrets = false): void
    {
        if ($config === []) {
            $output->writelnFormatted('No configuration values loaded.', ConsoleColor::Gray);

            return;
        }

        $rows = $this->rows($config, showSecrets: $showSecrets);

        $output->writelnFormatted('Configuration:', ConsoleColor::Yellow, styles: [ConsoleStyle::Bold]);
        $this->tables->render(ConsoleTable::make(
            ['Key', 'Value'],
            $this->tableRows($rows),
        ), $output);
    }

    /**
     * @param array<array-key, mixed> $config
     *
     * @return array<string, string>
     */
    private function rows(array $config, string $prefix = '', bool $showSecrets = false): array
    {
        $rows = [];

        foreach ($config as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix . '.' . (string) $key;

            if (is_array($value) && $value !== []) {
                foreach ($this->rows($value, $name, $showSecrets) as $childKey => $childValue) {
                    $rows[$childKey] = $childValue;
                }

                continue;
            }

            $rows[$name] = $this->value($name, $value, $showSecrets);
        }

        return $rows;
    }

    /**
     * @param array<string, string> $rows
     *
     * @return list<list<string>>
     */
    private function tableRows(array $rows): array
    {
        $tableRows = [];

        foreach ($rows as $key => $value) {
            $tableRows[] = [$key, $value];
        }

        return $tableRows;
    }

    private function value(string $key, mixed $value, bool $showSecrets): string
    {
        if (!$showSecrets && $this->isSecretKey($key)) {
            return '[redacted]';
        }

        if ($value === null) {
            return 'null';
        }

        if ($value === []) {
            return '[]';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $value;
        }

        return var_export($value, true);
    }

    private function isSecretKey(string $key): bool
    {
        return preg_match('/(^|[._-])(app_key|key|secret|password|token)([._-]|$)/i', $key) === 1;
    }
}
