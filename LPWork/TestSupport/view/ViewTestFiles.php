<?php

declare(strict_types=1);

namespace Tests\support\view;

use LPWork\Cache\CacheStore;
use LPWork\Translation\Translator;
use LPWork\View\Contracts\ViewEngine;
use LPWork\View\PhpViewEngine;
use LPWork\View\ViewFactory;
use LPWork\View\ViewFinder;
use RuntimeException;

final readonly class ViewTestFiles
{
    public static function create(string $root, string $path, string $contents): void
    {
        $file = $root . '/' . ltrim($path, '/');
        $directory = dirname($file);

        if (!is_dir($directory) && !mkdir($directory, recursive: true)) {
            throw new RuntimeException(sprintf('Could not create directory: %s', $directory));
        }

        if (file_put_contents($file, $contents) === false) {
            throw new RuntimeException(sprintf('Could not write view file: %s', $file));
        }
    }

    public static function factory(
        string $basePath,
        ?CacheStore $cache = null,
        ?ViewEngine $engine = null,
        ?Translator $translator = null,
    ): ViewFactory {
        return new ViewFactory(
            finder: new ViewFinder(['views'], $basePath, $cache),
            engine: $engine ?? new PhpViewEngine(),
            translator: $translator,
        );
    }
}
