<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

/**
 * Represents the about runtime snapshot framework component.
 */
final readonly class AboutRuntimeSnapshot
{
    /**
     * Creates a new AboutRuntimeSnapshot instance.
     */
    public function __construct(
        private string $name,
        private string $frameworkVersion,
        private string $basePath,
        private string $phpVersion,
        private string $phpSapi,
        private string $operatingSystem,
        private string $timezone,
        private string $environment,
        private bool $production,
        private bool $debug,
        private string $locale,
        private string $memoryLimit,
        private string $cacheDriver,
        private string $sessionDriver,
        private string $queueConnection,
        private string $databaseConnection,
        private string $mailTransport,
        private string $storageDisk,
        private string $lockDriver,
        private string $lockStore,
        private string $throttleStorage,
        private string $broadcastingConnection,
        private string $notificationDatabase,
        private string $schedulerHistory,
        private string $observabilityReporters,
        private string $maintenanceStore,
        private string $securityProfile,
        private string $securityHeaders,
        private string $csrfProtection,
        private int $loadedExtensions,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Performs the framework version operation.
     */
    public function frameworkVersion(): string
    {
        return $this->frameworkVersion;
    }

    /**
     * Performs the base path operation.
     */
    public function basePath(): string
    {
        return $this->basePath;
    }

    /**
     * Performs the php version operation.
     */
    public function phpVersion(): string
    {
        return $this->phpVersion;
    }

    /**
     * Performs the php sapi operation.
     */
    public function phpSapi(): string
    {
        return $this->phpSapi;
    }

    /**
     * Performs the operating system operation.
     */
    public function operatingSystem(): string
    {
        return $this->operatingSystem;
    }

    /**
     * Performs the timezone operation.
     */
    public function timezone(): string
    {
        return $this->timezone;
    }

    /**
     * Returns environment.
     */
    public function environment(): string
    {
        return $this->environment;
    }

    /**
     * Performs the production operation.
     */
    public function production(): bool
    {
        return $this->production;
    }

    /**
     * Performs the debug operation.
     */
    public function debug(): bool
    {
        return $this->debug;
    }

    /**
     * Performs the locale operation.
     */
    public function locale(): string
    {
        return $this->locale;
    }

    /**
     * Performs the memory limit operation.
     */
    public function memoryLimit(): string
    {
        return $this->memoryLimit;
    }

    /**
     * Performs the cache driver operation.
     */
    public function cacheDriver(): string
    {
        return $this->cacheDriver;
    }

    /**
     * Returns session driver.
     */
    public function sessionDriver(): string
    {
        return $this->sessionDriver;
    }

    /**
     * Returns queue connection.
     */
    public function queueConnection(): string
    {
        return $this->queueConnection;
    }

    /**
     * Returns database connection.
     */
    public function databaseConnection(): string
    {
        return $this->databaseConnection;
    }

    /**
     * Performs the mail transport operation.
     */
    public function mailTransport(): string
    {
        return $this->mailTransport;
    }

    /**
     * Performs the storage disk operation.
     */
    public function storageDisk(): string
    {
        return $this->storageDisk;
    }

    /**
     * Performs the lock driver operation.
     */
    public function lockDriver(): string
    {
        return $this->lockDriver;
    }

    /**
     * Performs the lock store operation.
     */
    public function lockStore(): string
    {
        return $this->lockStore;
    }

    /**
     * Performs the throttle storage operation.
     */
    public function throttleStorage(): string
    {
        return $this->throttleStorage;
    }

    /**
     * Runs broadcasting connection.
     */
    public function broadcastingConnection(): string
    {
        return $this->broadcastingConnection;
    }

    /**
     * Performs the notification database operation.
     */
    public function notificationDatabase(): string
    {
        return $this->notificationDatabase;
    }

    /**
     * Performs the scheduler history operation.
     */
    public function schedulerHistory(): string
    {
        return $this->schedulerHistory;
    }

    /**
     * Performs the observability reporters operation.
     */
    public function observabilityReporters(): string
    {
        return $this->observabilityReporters;
    }

    /**
     * Performs the maintenance store operation.
     */
    public function maintenanceStore(): string
    {
        return $this->maintenanceStore;
    }

    /**
     * Performs the security profile operation.
     */
    public function securityProfile(): string
    {
        return $this->securityProfile;
    }

    /**
     * Performs the security headers operation.
     */
    public function securityHeaders(): string
    {
        return $this->securityHeaders;
    }

    /**
     * Performs the csrf protection operation.
     */
    public function csrfProtection(): string
    {
        return $this->csrfProtection;
    }

    /**
     * Builds or returns loaded extensions.
     */
    public function loadedExtensions(): int
    {
        return $this->loadedExtensions;
    }
}
