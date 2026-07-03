<?php

declare(strict_types=1);

namespace LPWork\Observability;

/**
 * Represents the sensitive value redactor framework component.
 */
final readonly class SensitiveValueRedactor
{
    private const string REDACTED = '[redacted]';

    /**
     * @var list<string>
     */
    private const array SENSITIVE_PARTS = [
        'authorization',
        'cookie',
        'csrf',
        'key',
        'password',
        'secret',
        'token',
    ];

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    public function redact(array $values): array
    {
        $redacted = [];

        foreach ($values as $key => $value) {
            $redacted[$key] = $this->isSensitive($key)
                ? self::REDACTED
                : $this->redactValue($value);
        }

        return $redacted;
    }

    private function redactValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $redacted = [];

        foreach ($value as $key => $nestedValue) {
            $redacted[$key] = $this->isSensitive((string) $key)
                ? self::REDACTED
                : $this->redactValue($nestedValue);
        }

        return $redacted;
    }

    private function isSensitive(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::SENSITIVE_PARTS as $part) {
            if (str_contains($key, $part)) {
                return true;
            }
        }

        return false;
    }
}
