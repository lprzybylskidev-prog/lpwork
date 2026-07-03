<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\Storage\StorageManager;
use Throwable;

/**
 * Represents the storage health check framework component.
 */
final readonly class StorageHealthCheck implements HealthCheck
{
    /**
     * Creates a new StorageHealthCheck instance.
     */
    public function __construct(
        private StorageManager $storage,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'storage';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $path = 'framework/health/storage-' . bin2hex(random_bytes(8)) . '.txt';
        $value = 'ok';
        $diskName = $this->storage->defaultDiskName();
        $driverName = $this->storage->diskDriverName($diskName);

        try {
            $disk = $this->storage->default();
            $disk->put($path, $value);
            $stored = $disk->get($path);
            $disk->delete($path);
        } catch (Throwable $throwable) {
            return HealthCheckResult::unhealthy(
                $this->name(),
                sprintf('Storage disk [%s] using driver [%s] failed: %s.', $diskName, $driverName, $throwable::class),
            );
        }

        if ($stored !== $value) {
            return HealthCheckResult::unhealthy($this->name(), sprintf('Storage disk [%s] using driver [%s] did not return the probe value.', $diskName, $driverName));
        }

        return HealthCheckResult::healthy($this->name(), sprintf('Storage disk [%s] using driver [%s] is readable and writable.', $diskName, $driverName));
    }
}
