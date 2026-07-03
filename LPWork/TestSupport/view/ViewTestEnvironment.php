<?php

declare(strict_types=1);

namespace Tests\support\view;

use LPWork\Cache\CacheStore;
use LPWork\Filesystem\Filesystem;
use LPWork\Translation\Translator;
use LPWork\View\Contracts\ViewEngine;
use LPWork\View\ViewFactory;
use RuntimeException;

final readonly class ViewTestEnvironment
{
    private function __construct(private string $basePath) {}

    public static function create(): self
    {
        $basePath = sys_get_temp_dir() . '/lpwork_views_' . uniqid('', true);

        if (!mkdir($basePath)) {
            throw new RuntimeException('Could not create temporary view directory.');
        }

        return new self($basePath);
    }

    public function createView(string $path, string $contents): void
    {
        ViewTestFiles::create($this->basePath, $path, $contents);
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function factory(?CacheStore $cache = null, ?ViewEngine $engine = null, ?Translator $translator = null): ViewFactory
    {
        return ViewTestFiles::factory($this->basePath, $cache, $engine, $translator);
    }

    public function remove(): void
    {
        new Filesystem()->clearDirectory($this->basePath);
        rmdir($this->basePath);
    }
}
