<?php

declare(strict_types=1);

namespace LPWork\Translation;

use Closure;

use function is_scalar;

use const MB_CASE_TITLE;

use function mb_convert_case;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function preg_replace_callback;

use Stringable;

/**
 * Represents the translation parameter formatter framework component.
 */
final readonly class TranslationParameterFormatter
{
    /**
     * @param array<string, scalar|Stringable|null> $parameters
     * @param Closure(string): ?string $translateValue
     */
    public function replace(string $line, array $parameters, Closure $translateValue): string
    {
        return preg_replace_callback(
            '/:([A-Za-z_][A-Za-z0-9_]*)(?::([A-Za-z_][A-Za-z0-9_]*))?/',
            function (array $matches) use ($parameters, $translateValue): string {
                $placeholder = $matches[1];
                $modifier = $matches[2] ?? null;
                $key = $this->parameterKey($placeholder, $parameters);

                if ($key === null) {
                    return $matches[0];
                }

                $value = $this->stringValue($parameters[$key]);
                $translated = $translateValue($value) ?? $value;

                return $this->transform($translated, $modifier ?? $this->implicitModifier($placeholder, $key));
            },
            $line,
        ) ?? $line;
    }

    /**
     * @param array<string, scalar|Stringable|null> $parameters
     */
    private function parameterKey(string $placeholder, array $parameters): ?string
    {
        if (isset($parameters[$placeholder]) || array_key_exists($placeholder, $parameters)) {
            return $placeholder;
        }

        $lower = mb_strtolower($placeholder, 'UTF-8');

        return isset($parameters[$lower]) || array_key_exists($lower, $parameters) ? $lower : null;
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        return '';
    }

    private function implicitModifier(string $placeholder, string $key): ?string
    {
        if ($placeholder === mb_strtoupper($key, 'UTF-8')) {
            return 'upper';
        }

        if ($placeholder !== $key && mb_substr($placeholder, 0, 1, 'UTF-8') === mb_strtoupper(mb_substr($placeholder, 0, 1, 'UTF-8'), 'UTF-8')) {
            return 'ucfirst';
        }

        return null;
    }

    private function transform(string $value, ?string $modifier): string
    {
        return match ($modifier) {
            'lower' => mb_strtolower($value, 'UTF-8'),
            'upper' => mb_strtoupper($value, 'UTF-8'),
            'ucfirst' => $this->ucfirst($value),
            'title' => mb_convert_case($value, MB_CASE_TITLE, 'UTF-8'),
            default => $value,
        };
    }

    private function ucfirst(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($value, 1, null, 'UTF-8');
    }
}
