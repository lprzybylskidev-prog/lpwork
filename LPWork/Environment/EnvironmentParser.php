<?php

declare(strict_types=1);

namespace LPWork\Environment;

use LPWork\Environment\Exceptions\FileReadException;
use LPWork\Environment\Exceptions\InvalidLineStructureException;

/**
 * Represents the environment parser framework component.
 */
final class EnvironmentParser
{
    /**
     * @return array<string, string>
     */
    public function parse(string $content, string $path): array
    {
        $parsedContent = [];

        foreach ($this->splitContent($content, $path) as $lineNumber => $line) {
            $lineNumber++;

            $this->validateLine($line, $lineNumber);

            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$envKey, $envValue] = explode('=', $line, 2);
            $envKey = trim($envKey);

            $parsedContent[$envKey] = $this->parseValue($envValue);
        }

        return $parsedContent;
    }

    private function parseValue(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $firstChar = $value[0];
        $lastChar = $value[strlen($value) - 1];

        if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
            $quote = $firstChar;

            $value = substr($value, 1, -1);

            return str_replace('\\' . $quote, $quote, $value);
        }

        return $value;
    }

    private function validateLine(string $line, int $lineNumber): void
    {
        $trimmedLine = trim($line);

        if ($trimmedLine === '' || str_starts_with($trimmedLine, '#')) {
            return;
        }

        $pattern = '/^([a-zA-Z0-9_]+)\s*=\s*(?:'
            . '[^\s\'"]+'
            . '|'
            . '"(?:\\\\.|[^"\\\\])*"'
            . '|'
            . "'(?:\\\\.|[^'\\\\])*'"
            . ')?$/';

        if (preg_match($pattern, $trimmedLine) !== 1) {
            throw new InvalidLineStructureException($trimmedLine, $lineNumber);
        }
    }

    /**
     * @return list<string>
     */
    private function splitContent(string $content, string $path): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $content);

        if ($lines === false) {
            throw new FileReadException($path);
        }

        return $lines;
    }
}
