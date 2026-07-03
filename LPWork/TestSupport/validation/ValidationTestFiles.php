<?php

declare(strict_types=1);

namespace Tests\support\validation;

use RuntimeException;

final readonly class ValidationTestFiles
{
    public static function file(string $name, string $contents): string
    {
        $directory = \Tests\support\ProjectPaths::root() . '/storage/testing/validation-files';

        if (!is_dir($directory) && !mkdir($directory, recursive: true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create validation test directory.');
        }

        $path = $directory . '/' . $name;

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Could not write validation test file.');
        }

        return $path;
    }

    public static function image(string $name = 'pixel.png'): string
    {
        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADggGOr6nltQAAAABJRU5ErkJggg==',
            strict: true,
        );

        if ($contents === false) {
            throw new RuntimeException('Could not decode validation test image.');
        }

        return self::file($name, $contents);
    }

    public static function remove(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
