<?php

declare(strict_types=1);

namespace LPWork\Validation;

use const FILEINFO_MIME_TYPE;

use function finfo_file;
use function finfo_open;
use function is_array;
use function is_int;
use function is_string;

use LPWork\Filesystem\Filesystem;
use LPWork\Requests\UploadedFile;

use function pathinfo;

use const PATHINFO_EXTENSION;

use function strtolower;

use Throwable;

use const UPLOAD_ERR_OK;

/**
 * Represents the file validation reader framework component.
 */
final readonly class FileValidationReader
{
    /**
     * Creates a new FileValidationReader instance.
     */
    public function __construct(
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Reports whether is file.
     */
    public function isFile(mixed $value): bool
    {
        $path = $this->path($value);

        return $path !== null && $this->filesystem->isFile($path);
    }

    /**
     * Returns path.
     */
    public function path(mixed $value): ?string
    {
        if ($value instanceof UploadedFile) {
            return $value->isValid() ? $value->temporaryPath() : null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (!is_array($value)) {
            return null;
        }

        $error = $value['error'] ?? UPLOAD_ERR_OK;

        if (is_int($error) && $error !== UPLOAD_ERR_OK) {
            return null;
        }

        $path = $value['tmp_name'] ?? $value['path'] ?? null;

        return is_string($path) && $path !== '' ? $path : null;
    }

    /**
     * Performs the size operation.
     */
    public function size(mixed $value): ?int
    {
        $path = $this->path($value);

        if ($path === null || !$this->filesystem->isFile($path)) {
            return null;
        }

        if ($value instanceof UploadedFile) {
            return $value->size();
        }

        if (is_array($value) && is_int($value['size'] ?? null)) {
            return $value['size'];
        }

        $size = @filesize($path);

        return is_int($size) ? $size : null;
    }

    /**
     * Performs the extension operation.
     */
    public function extension(mixed $value): ?string
    {
        $path = $this->path($value);

        if ($path === null || !$this->filesystem->isFile($path)) {
            return null;
        }

        if ($value instanceof UploadedFile) {
            return $value->clientExtension();
        }

        if (is_array($value) && is_string($value['name'] ?? null)) {
            $extension = pathinfo($value['name'], PATHINFO_EXTENSION);

            return $extension === '' ? null : strtolower($extension);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension === '' ? null : strtolower($extension);
    }

    /**
     * Performs the mime operation.
     */
    public function mime(mixed $value): ?string
    {
        $path = $this->path($value);

        if ($path === null || !$this->filesystem->isFile($path)) {
            return null;
        }

        try {
            $info = finfo_open(FILEINFO_MIME_TYPE);

            if ($info === false) {
                return $this->clientMime($value);
            }

            $mime = finfo_file($info, $path);
        } catch (Throwable) {
            return $this->clientMime($value);
        }

        return is_string($mime) ? strtolower($mime) : $this->clientMime($value);
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    public function dimensions(mixed $value): ?array
    {
        $path = $this->path($value);

        if ($path === null || !$this->filesystem->isFile($path)) {
            return null;
        }

        $size = @getimagesize($path);

        if (!is_array($size)) {
            return null;
        }

        return [$size[0], $size[1]];
    }

    private function clientMime(mixed $value): ?string
    {
        if ($value instanceof UploadedFile && $value->clientMimeType() !== '') {
            return strtolower($value->clientMimeType());
        }

        if (is_array($value) && is_string($value['type'] ?? null) && $value['type'] !== '') {
            return strtolower($value['type']);
        }

        return null;
    }
}
