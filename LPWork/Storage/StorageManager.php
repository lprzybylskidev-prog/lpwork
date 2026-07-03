<?php

declare(strict_types=1);

namespace LPWork\Storage;

use LPWork\Config\ArrayConfigReader;
use LPWork\Config\NamedDriverConfig;
use LPWork\Config\NamedDriverConfigFactory;
use LPWork\Storage\Exceptions\InvalidStorageConfigException;
use LPWork\Storage\Exceptions\InvalidStorageDiskException;
use LPWork\Storage\Exceptions\MissingStorageConfigException;

/**
 * Resolves configured storage disks and exposes disk metadata.
 */
final class StorageManager
{
    /**
     * @var array<string, StorageDisk>
     */
    private array $disks = [];

    private NamedDriverConfig $diskConfig;

    private StorageDriverFactory $driverFactory;

    /**
     * @param array<array-key, mixed> $config
     */
    public function __construct(
        private readonly array $config,
        private readonly string $basePath,
        ?StorageDriverFactory $driverFactory = null,
    ) {
        $this->diskConfig = $this->diskConfig($config);
        $this->driverFactory = $driverFactory ?? new StorageDriverFactory($this->basePath);
    }

    /**
     * Returns the configured default storage disk.
     */
    public function default(): StorageDisk
    {
        return $this->disk($this->defaultDiskName());
    }

    /**
     * Returns the configured storage disk name used when no disk is requested explicitly.
     */
    public function defaultDiskName(): string
    {
        return $this->diskConfig->defaultName();
    }

    /**
     * Builds a public URL for a path on the selected disk.
     */
    public function url(string $path, ?string $disk = null): string
    {
        return ($disk === null ? $this->default() : $this->disk($disk))->url($path);
    }

    /**
     * Returns a named storage disk, creating and caching it on first use.
     */
    public function disk(string $name): StorageDisk
    {
        if (array_key_exists($name, $this->disks)) {
            return $this->disks[$name];
        }

        $config = $this->diskConfig->entry($name, static fn(string $name): InvalidStorageDiskException => new InvalidStorageDiskException($name));
        $reader = $this->reader($config);

        $this->disks[$name] = new StorageDisk(
            name: $name,
            driver: $this->driverFactory->create($config, $this->diskConfig->entryKey($name)),
            url: $reader->optionalString('url', "{$this->diskConfig->entryKey($name)}.url"),
        );

        return $this->disks[$name];
    }

    /**
     * Returns all configured storage disk names.
     *
     * @return list<string>
     */
    public function diskNames(): array
    {
        return $this->diskConfig->names();
    }

    /**
     * Returns the configured driver type for a named storage disk.
     */
    public function diskDriverName(string $name): string
    {
        $config = $this->diskConfig->entry($name, static fn(string $name): InvalidStorageDiskException => new InvalidStorageDiskException($name));
        $driver = $config['driver'] ?? null;

        return is_string($driver) && $driver !== '' ? $driver : 'unknown';
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingStorageConfigException => new MissingStorageConfigException($key),
            invalidException: static fn(string $key): InvalidStorageConfigException => new InvalidStorageConfigException($key),
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function diskConfig(array $config): NamedDriverConfig
    {
        return new NamedDriverConfigFactory()->create(
            config: $config,
            entriesKey: 'disks',
            missingException: static fn(string $key): MissingStorageConfigException => new MissingStorageConfigException($key),
            invalidException: static fn(string $key): InvalidStorageConfigException => new InvalidStorageConfigException($key),
        );
    }
}
