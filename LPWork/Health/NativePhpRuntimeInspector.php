<?php

declare(strict_types=1);

namespace LPWork\Health;

use LPWork\Health\Contracts\PhpRuntimeInspector;
use PDO;

/**
 * Represents the native php runtime inspector framework component.
 */
final readonly class NativePhpRuntimeInspector implements PhpRuntimeInspector
{
    /**
     * Performs the php version id operation.
     */
    public function phpVersionId(): int
    {
        return PHP_VERSION_ID;
    }

    /**
     * Performs the php version operation.
     */
    public function phpVersion(): string
    {
        return PHP_VERSION;
    }

    /**
     * Performs the extension loaded operation.
     */
    public function extensionLoaded(string $extension): bool
    {
        return extension_loaded($extension);
    }

    /**
     * Performs the pdo drivers operation.
     */
    public function pdoDrivers(): array
    {
        $drivers = [];

        foreach (PDO::getAvailableDrivers() as $driver) {
            if (is_string($driver)) {
                $drivers[] = $driver;
            }
        }

        return $drivers;
    }
}
