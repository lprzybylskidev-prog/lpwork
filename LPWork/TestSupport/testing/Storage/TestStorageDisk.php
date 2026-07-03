<?php

declare(strict_types=1);

namespace Tests\support\testing\Storage;

use LPWork\Storage\Drivers\MemoryStorageDriver;
use LPWork\Storage\StorageDisk;
use PHPUnit\Framework\Assert;

final readonly class TestStorageDisk
{
    private function __construct(
        private StorageDisk $disk,
    ) {}

    public static function create(string $name = 'test', ?string $url = null): self
    {
        return new self(new StorageDisk($name, new MemoryStorageDriver(), $url));
    }

    public function disk(): StorageDisk
    {
        return $this->disk;
    }

    public function put(string $path, string $contents): self
    {
        $this->disk->put($path, $contents);

        return $this;
    }

    public function append(string $path, string $contents): self
    {
        $this->disk->append($path, $contents);

        return $this;
    }

    public function delete(string $path): self
    {
        $this->disk->delete($path);

        return $this;
    }

    public function clear(string $path): self
    {
        $this->disk->clear($path);

        return $this;
    }

    public function assertExists(string $path): self
    {
        Assert::assertTrue($this->disk->exists($path), sprintf('Storage file [%s] does not exist.', $path));

        return $this;
    }

    public function assertMissing(string $path): self
    {
        Assert::assertFalse($this->disk->exists($path), sprintf('Storage file [%s] exists unexpectedly.', $path));

        return $this;
    }

    public function assertContents(string $path, string $contents): self
    {
        Assert::assertSame($contents, $this->disk->get($path), sprintf('Unexpected storage contents for [%s].', $path));

        return $this;
    }
}
