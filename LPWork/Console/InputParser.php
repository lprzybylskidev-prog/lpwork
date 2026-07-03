<?php

declare(strict_types=1);

namespace LPWork\Console;

use function array_key_exists;
use function array_slice;
use function count;
use function explode;
use function str_contains;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Represents the input parser framework component.
 */
final class InputParser
{
    /**
     * @param array<int, string> $argv
     *
     * @return array{arguments: list<string>, options: array<string, string|bool|int|list<string|bool|int>>}
     */
    public function parse(array $argv): array
    {
        $arguments = [];
        $options = [];
        $tokens = array_slice($argv, 2);
        $parseOptions = true;

        for ($index = 0; $index < count($tokens); $index++) {
            $token = $tokens[$index];

            if ($parseOptions && $token === '--') {
                $parseOptions = false;

                continue;
            }

            if ($parseOptions && $this->isLongOption($token)) {
                $this->parseLongOption($token, $tokens, $index, $options);

                continue;
            }

            if ($parseOptions && $this->isShortOption($token)) {
                $this->parseShortOption($token, $tokens, $index, $options);

                continue;
            }

            $arguments[] = $token;
        }

        return [
            'arguments' => $arguments,
            'options' => $options,
        ];
    }

    /**
     * @param list<string> $tokens
     * @param array<string, string|bool|int|list<string|bool|int>> $options
     */
    private function parseLongOption(string $token, array $tokens, int &$index, array &$options): void
    {
        $option = substr($token, 2);

        if (str_starts_with($option, 'no-') && !str_contains($option, '=')) {
            $this->addOption($options, substr($option, 3), false);

            return;
        }

        if (str_contains($option, '=')) {
            [$name, $value] = explode('=', $option, 2);
            $this->addOption($options, $name, $value);

            return;
        }

        $nextIndex = $index + 1;

        if (array_key_exists($nextIndex, $tokens) && $tokens[$nextIndex] !== '--' && !$this->isOptionToken($tokens[$nextIndex])) {
            $this->addOption($options, $option, $tokens[$nextIndex]);
            $index++;

            return;
        }

        $this->addOption($options, $option, true);
    }

    /**
     * @param list<string> $tokens
     * @param array<string, string|bool|int|list<string|bool|int>> $options
     */
    private function parseShortOption(string $token, array $tokens, int &$index, array &$options): void
    {
        $option = substr($token, 1);

        if (str_contains($option, '=') && strlen($option) > 2) {
            [$name, $value] = explode('=', $option, 2);
            $this->addOption($options, $name, $value);

            return;
        }

        if (strlen($option) > 1) {
            $this->parseShortOptionGroup($option, $options);

            return;
        }

        $nextIndex = $index + 1;

        if (array_key_exists($nextIndex, $tokens) && $tokens[$nextIndex] !== '--' && !$this->isOptionToken($tokens[$nextIndex])) {
            $this->addOption($options, $option, $tokens[$nextIndex]);
            $index++;

            return;
        }

        $this->addOption($options, $option, true);
    }

    /**
     * @param array<string, string|bool|int|list<string|bool|int>> $options
     */
    private function parseShortOptionGroup(string $option, array &$options): void
    {
        $firstOption = $option[0];
        $length = strlen($option);
        $isRepeatedOption = true;

        for ($index = 0; $index < $length; $index++) {
            if ($option[$index] !== $firstOption) {
                $isRepeatedOption = false;

                break;
            }
        }

        if ($isRepeatedOption) {
            $this->addOption($options, $firstOption, $length);

            return;
        }

        for ($index = 0; $index < $length; $index++) {
            $this->addOption($options, $option[$index], true);
        }
    }

    /**
     * @param array<string, string|bool|int|list<string|bool|int>> $options
     */
    private function addOption(array &$options, string $name, string|bool|int $value): void
    {
        if (!array_key_exists($name, $options)) {
            $options[$name] = $value;

            return;
        }

        $current = $options[$name];

        if (is_array($current)) {
            $current[] = $value;
            $options[$name] = $current;

            return;
        }

        $options[$name] = [$current, $value];
    }

    private function isOptionToken(string $token): bool
    {
        return $this->isLongOption($token) || $this->isShortOption($token);
    }

    private function isLongOption(string $token): bool
    {
        return str_starts_with($token, '--') && $token !== '--';
    }

    private function isShortOption(string $token): bool
    {
        return str_starts_with($token, '-') && !str_starts_with($token, '--') && $token !== '-';
    }
}
