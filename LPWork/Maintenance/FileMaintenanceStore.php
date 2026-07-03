<?php

declare(strict_types=1);

namespace LPWork\Maintenance;

use JsonException;
use LPWork\Filesystem\Filesystem;
use LPWork\Maintenance\Exceptions\InvalidMaintenanceStateException;

/**
 * Represents the file maintenance store framework component.
 */
final readonly class FileMaintenanceStore implements MaintenanceStore
{
    /**
     * Creates a new FileMaintenanceStore instance.
     */
    public function __construct(
        private Filesystem $filesystem,
        private string $path,
    ) {}

    /**
     * Builds or returns read.
     */
    public function read(): MaintenanceState
    {
        if (!$this->filesystem->isFile($this->path)) {
            return MaintenanceState::inactive();
        }

        try {
            $payload = json_decode($this->filesystem->read($this->path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InvalidMaintenanceStateException::forUnreadablePayload($this->path, $exception);
        }

        if (!is_array($payload)) {
            throw InvalidMaintenanceStateException::forUnreadablePayload($this->path);
        }

        $retryAfter = $payload['retry_after'] ?? null;

        if ($retryAfter !== null && !is_string($retryAfter)) {
            throw InvalidMaintenanceStateException::forUnreadablePayload($this->path);
        }

        return MaintenanceState::active($retryAfter);
    }

    /**
     * Registers or stores write.
     */
    public function write(MaintenanceState $state): void
    {
        if (!$state->isActive()) {
            $this->clear();

            return;
        }

        try {
            $payload = json_encode([
                'retry_after' => $state->retryAfter(),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InvalidMaintenanceStateException::forUnwritablePayload($this->path, $exception);
        }

        $this->filesystem->write($this->path, $payload . "\n");
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(): void
    {
        $this->filesystem->delete($this->path);
    }
}
