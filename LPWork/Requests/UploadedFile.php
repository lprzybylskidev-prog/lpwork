<?php

declare(strict_types=1);

namespace LPWork\Requests;

use function pathinfo;

use const PATHINFO_EXTENSION;

use function strtolower;

use const UPLOAD_ERR_OK;

/**
 * Represents the uploaded file framework component.
 */
final readonly class UploadedFile
{
    /**
     * Creates a new UploadedFile instance.
     */
    public function __construct(
        private string $temporaryPath,
        private string $clientName,
        private string $clientMimeType = '',
        private int $size = 0,
        private int $error = UPLOAD_ERR_OK,
    ) {}

    /**
     * Performs the temporary path operation.
     */
    public function temporaryPath(): string
    {
        return $this->temporaryPath;
    }

    /**
     * Performs the client name operation.
     */
    public function clientName(): string
    {
        return $this->clientName;
    }

    /**
     * Performs the client mime type operation.
     */
    public function clientMimeType(): string
    {
        return $this->clientMimeType;
    }

    /**
     * Performs the size operation.
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * Performs the error operation.
     */
    public function error(): int
    {
        return $this->error;
    }

    /**
     * Reports whether is valid.
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && $this->temporaryPath !== '';
    }

    /**
     * Performs the client extension operation.
     */
    public function clientExtension(): ?string
    {
        $extension = pathinfo($this->clientName, PATHINFO_EXTENSION);

        return $extension === '' ? null : strtolower($extension);
    }
}
