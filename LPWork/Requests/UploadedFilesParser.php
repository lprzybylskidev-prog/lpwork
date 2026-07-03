<?php

declare(strict_types=1);

namespace LPWork\Requests;

use function array_key_exists;
use function ctype_digit;
use function is_array;
use function is_int;
use function is_scalar;
use function is_string;

use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

/**
 * Represents the uploaded files parser framework component.
 */
final readonly class UploadedFilesParser
{
    /**
     * @param array<string, mixed> $files
     *
     * @return array<string, mixed>
     */
    public function parse(array $files): array
    {
        $parsed = [];

        foreach ($files as $key => $file) {
            $uploaded = $this->parseFile($file);

            if ($uploaded !== null) {
                $parsed[$key] = $uploaded;
            }
        }

        return $parsed;
    }

    private function parseFile(mixed $file): mixed
    {
        if ($file instanceof UploadedFile) {
            return $file;
        }

        if (!is_array($file)) {
            return null;
        }

        if ($this->isPhpFileShape($file)) {
            return $this->parsePhpFileShape($file);
        }

        $parsed = [];

        foreach ($file as $key => $value) {
            $uploaded = $this->parseFile($value);

            if ($uploaded !== null) {
                $parsed[$key] = $uploaded;
            }
        }

        return $parsed === [] ? null : $parsed;
    }

    /**
     * @param array<array-key, mixed> $file
     */
    private function isPhpFileShape(array $file): bool
    {
        return array_key_exists('name', $file)
            && array_key_exists('tmp_name', $file)
            && array_key_exists('error', $file);
    }

    /**
     * @param array<array-key, mixed> $file
     */
    private function parsePhpFileShape(array $file): mixed
    {
        if (is_array($file['name']) || is_array($file['tmp_name']) || is_array($file['error'])) {
            return $this->parseNestedPhpFileShape($file);
        }

        $error = $this->integerValue($file['error'] ?? UPLOAD_ERR_OK);

        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $temporaryPath = $this->stringValue($file['tmp_name'] ?? '');
        $clientName = $this->stringValue($file['name'] ?? '');
        $clientMimeType = $this->stringValue($file['type'] ?? '');
        $size = $this->integerValue($file['size'] ?? 0);

        return new UploadedFile(
            temporaryPath: $temporaryPath,
            clientName: $clientName,
            clientMimeType: $clientMimeType,
            size: $size,
            error: $error,
        );
    }

    /**
     * @param array<array-key, mixed> $file
     *
     * @return array<array-key, mixed>|null
     */
    private function parseNestedPhpFileShape(array $file): ?array
    {
        $names = is_array($file['name'] ?? null) ? $file['name'] : [];
        $parsed = [];

        foreach ($names as $key => $name) {
            $uploaded = $this->parsePhpFileShape([
                'name' => $name,
                'tmp_name' => $this->nestedValue($file['tmp_name'] ?? [], $key),
                'type' => $this->nestedValue($file['type'] ?? [], $key),
                'size' => $this->nestedValue($file['size'] ?? [], $key),
                'error' => $this->nestedValue($file['error'] ?? [], $key),
            ]);

            if ($uploaded !== null) {
                $parsed[$key] = $uploaded;
            }
        }

        return $parsed === [] ? null : $parsed;
    }

    private function nestedValue(mixed $values, int|string $key): mixed
    {
        if (!is_array($values) || !array_key_exists($key, $values)) {
            return null;
        }

        return $values[$key];
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private function integerValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return 0;
    }
}
