<?php

declare(strict_types=1);

namespace Tests\support\testing\Filesystem;

use LPWork\Filesystem\Filesystem;
use PHPUnit\Framework\Assert;
use Tests\support\exceptions\TestSupportException;

final readonly class TestFilesystem
{
    private function __construct(
        private string $root,
        private Filesystem $filesystem,
    ) {}

    public static function create(): self
    {
        $root = sys_get_temp_dir() . '/lpwork_test_filesystem_' . uniqid('', true);

        if (!mkdir($root)) {
            throw TestSupportException::temporaryDirectoryCouldNotBeCreated($root);
        }

        return new self($root, new Filesystem());
    }

    public function root(string $path = ''): string
    {
        if ($path === '') {
            return $this->root;
        }

        return $this->filesystem->resolvePath($this->root, $path);
    }

    public function write(string $path, string $contents): self
    {
        $this->filesystem->write($this->root($path), $contents);

        return $this;
    }

    public function append(string $path, string $contents): self
    {
        $this->filesystem->append($this->root($path), $contents);

        return $this;
    }

    public function read(string $path): string
    {
        return $this->filesystem->read($this->root($path));
    }

    public function delete(string $path): self
    {
        $this->filesystem->delete($this->root($path));

        return $this;
    }

    public function cleanup(): void
    {
        $this->filesystem->clearDirectory($this->root);

        if (is_dir($this->root) && !rmdir($this->root)) {
            throw TestSupportException::testDirectoryCouldNotBeRead($this->root);
        }
    }

    public function assertFileExists(string $path): self
    {
        Assert::assertTrue($this->filesystem->isFile($this->root($path)), sprintf('Test file [%s] does not exist.', $path));

        return $this;
    }

    public function assertFileMissing(string $path): self
    {
        Assert::assertFalse($this->filesystem->exists($this->root($path)), sprintf('Test file [%s] exists unexpectedly.', $path));

        return $this;
    }

    public function assertFileContains(string $path, string $text): self
    {
        Assert::assertStringContainsString($text, $this->read($path));

        return $this;
    }

    public function assertFileEquals(string $path, string $contents): self
    {
        Assert::assertSame($contents, $this->read($path), sprintf('Unexpected test file contents for [%s].', $path));

        return $this;
    }
}
