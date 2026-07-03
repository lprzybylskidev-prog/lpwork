<?php

declare(strict_types=1);

namespace LPWork\Frontend;

use LPWork\Filesystem\Filesystem;
use LPWork\Frontend\Contracts\ApplicationAssetDevServerProbe;
use LPWork\Frontend\Exceptions\ApplicationAssetBuiltFileMissingException;
use LPWork\Frontend\Exceptions\ApplicationAssetDevServerUnavailableException;
use LPWork\Frontend\Exceptions\ApplicationAssetEntryNotFoundException;
use LPWork\Frontend\Exceptions\ApplicationAssetSourceFileMissingException;

/**
 * Renders application asset renderer output.
 */
final readonly class ApplicationAssetRenderer
{
    /**
     * Creates a new ApplicationAssetRenderer instance.
     */
    public function __construct(
        private string $basePath,
        private AssetEntryRegistry $entries,
        private ApplicationAssetManifestReader $manifests,
        private ApplicationAssetRenderMode $mode,
        private string $devServerUrl = 'http://localhost:5173',
        private string $buildPublicPath = '/build',
        private Filesystem $files = new Filesystem(),
        private ApplicationAssetDevServerProbe $devServers = new SocketApplicationAssetDevServerProbe(),
    ) {}

    /**
     * Performs the entry operation.
     */
    public function entry(string $name): string
    {
        $entry = $this->entries->get($name) ?? throw new ApplicationAssetEntryNotFoundException($name);
        $this->assertSourceFileExists($entry);

        return match ($this->mode) {
            ApplicationAssetRenderMode::DevServer => $this->devServerEntry($entry),
            ApplicationAssetRenderMode::Manifest => $this->manifestEntry($entry),
        };
    }

    private function devServerEntry(AssetEntry $entry): string
    {
        if (!$this->devServers->reachable($this->devServerUrl())) {
            throw new ApplicationAssetDevServerUnavailableException($this->devServerUrl());
        }

        return implode("\n", [
            sprintf('<script type="module" src="%s/@vite/client"></script>', $this->escape($this->devServerUrl())),
            sprintf('<script type="module" src="%s/%s"></script>', $this->escape($this->devServerUrl()), $this->escape($entry->sourcePath())),
        ]);
    }

    private function manifestEntry(AssetEntry $entry): string
    {
        $manifestEntry = $this->manifests->read()->entry($entry->sourcePath());
        $tags = [];

        foreach ($manifestEntry->css() as $css) {
            $this->assertBuiltAssetExists($css);
            $tags[] = sprintf('<link rel="stylesheet" href="%s">', $this->escape($this->publicPath($css)));
        }

        $this->assertBuiltAssetExists($manifestEntry->file());
        $tags[] = sprintf('<script type="module" src="%s"></script>', $this->escape($this->publicPath($manifestEntry->file())));

        return implode("\n", $tags);
    }

    private function assertSourceFileExists(AssetEntry $entry): void
    {
        $path = $this->basePath . '/' . $entry->sourcePath();

        if (!$this->files->isFile($path)) {
            throw new ApplicationAssetSourceFileMissingException($entry->name(), $path);
        }
    }

    private function assertBuiltAssetExists(string $asset): void
    {
        $path = $this->basePath . '/public/build/' . ltrim($asset, '/');

        if (!$this->files->isFile($path)) {
            throw new ApplicationAssetBuiltFileMissingException($asset, $path);
        }
    }

    private function publicPath(string $path): string
    {
        return rtrim($this->buildPublicPath, '/') . '/' . ltrim($path, '/');
    }

    private function devServerUrl(): string
    {
        return rtrim($this->devServerUrl, '/');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
