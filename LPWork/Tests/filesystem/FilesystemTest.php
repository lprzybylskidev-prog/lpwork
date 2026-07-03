<?php

declare(strict_types=1);

use LPWork\Filesystem\Exceptions\FileDeleteException;
use LPWork\Filesystem\Exceptions\FileNotFoundException;
use LPWork\Filesystem\Exceptions\InvalidPathException;
use LPWork\Filesystem\Filesystem;
use Tests\support\testing\Filesystem\TestFilesystem;

it('reads and writes binary-safe file contents', function (): void {
    $files = TestFilesystem::create();
    $filesystem = new Filesystem();

    try {
        $path = $files->root('nested/payload.bin');
        $contents = "abc\0def";

        $filesystem->write($path, $contents);

        expect($filesystem->exists($path))->toBeTrue()
            ->and($filesystem->isFile($path))->toBeTrue()
            ->and($filesystem->read($path))->toBe($contents);
    } finally {
        $files->cleanup();
    }
});

it('appends file contents', function (): void {
    $files = TestFilesystem::create();
    $filesystem = new Filesystem();

    try {
        $path = $files->root('logs/app.log');

        $filesystem->write($path, 'first');
        $filesystem->append($path, ' second');

        expect($filesystem->read($path))->toBe('first second');
    } finally {
        $files->cleanup();
    }
});

it('writes files only when they are missing', function (): void {
    $files = TestFilesystem::create();
    $filesystem = new Filesystem();

    try {
        $path = $files->root('cache/item.txt');

        expect($filesystem->writeIfMissing($path, 'first'))->toBeTrue()
            ->and($filesystem->writeIfMissing($path, 'second'))->toBeFalse()
            ->and($filesystem->read($path))->toBe('first');
    } finally {
        $files->cleanup();
    }
});

it('reports readable files and lists files by pattern', function (): void {
    $files = TestFilesystem::create();
    $filesystem = new Filesystem();

    try {
        $first = $files->root('first.php');
        $second = $files->root('second.php');

        $filesystem->write($first, '<?php return [];');
        $filesystem->write($second, '<?php return [];');

        expect($filesystem->isReadable($first))->toBeTrue()
            ->and($filesystem->files($files->root() . '/*.php'))->toBe([$first, $second]);
    } finally {
        $files->cleanup();
    }
});

it('throws when reading a missing file', function (): void {
    $filesystem = new Filesystem();

    expect(fn(): string => $filesystem->read(sys_get_temp_dir() . '/lpwork_missing_file'))
        ->toThrow(FileNotFoundException::class);
});

it('deletes files idempotently', function (): void {
    $files = TestFilesystem::create();
    $filesystem = new Filesystem();

    try {
        $path = $files->root('delete-me.txt');

        $filesystem->write($path, 'delete me');
        $filesystem->delete($path);
        $filesystem->delete($path);

        expect($filesystem->exists($path))->toBeFalse();
    } finally {
        $files->cleanup();
    }
});

it('rejects deleting a directory as a file', function (): void {
    $files = TestFilesystem::create();
    $filesystem = new Filesystem();

    try {
        $directory = $files->root('directory');
        $filesystem->makeDirectory($directory);

        expect(fn() => $filesystem->delete($directory))
            ->toThrow(FileDeleteException::class);
    } finally {
        $files->cleanup();
    }
});

it('clears directory contents while keeping the directory', function (): void {
    $files = TestFilesystem::create();
    $filesystem = new Filesystem();

    try {
        $directory = $files->root('clear');

        $filesystem->write($directory . '/first.txt', 'first');
        $filesystem->write($directory . '/nested/second.txt', 'second');
        $filesystem->clearDirectory($directory);

        expect($filesystem->isDirectory($directory))->toBeTrue()
            ->and($filesystem->exists($directory . '/first.txt'))->toBeFalse()
            ->and($filesystem->exists($directory . '/nested'))->toBeFalse();
    } finally {
        $files->cleanup();
    }
});

it('uses readable existing lock files without requiring write access to the lock file', function (): void {
    $files = TestFilesystem::create();
    $filesystem = new Filesystem();
    $path = $files->root('locks/cache.lock');

    try {
        $filesystem->write($path, '');
        chmod($path, 0o444);

        $value = $filesystem->withExclusiveLock($path, static fn(): string => 'locked');

        expect($value)->toBe('locked');
    } finally {
        chmod($path, 0o644);
        $files->cleanup();
    }
});

it('resolves safe relative paths inside a root path', function (): void {
    $filesystem = new Filesystem();

    expect($filesystem->resolvePath('/app/storage', 'cache//./items/value.bin'))
        ->toBe('/app/storage/cache/items/value.bin');
});

it('rejects unsafe paths', function (string $path): void {
    $filesystem = new Filesystem();

    expect(fn(): string => $filesystem->normalizeRelativePath($path))
        ->toThrow(InvalidPathException::class);
})->with([
    '',
    '/absolute/path.txt',
    '../escape.txt',
    'nested/../escape.txt',
    "bad\0path.txt",
    'php://memory',
]);
