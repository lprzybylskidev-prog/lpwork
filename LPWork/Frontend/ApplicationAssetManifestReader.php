<?php

declare(strict_types=1);

namespace LPWork\Frontend;

use JsonException;
use LPWork\Filesystem\Filesystem;
use LPWork\Frontend\Exceptions\ApplicationAssetManifestInvalidException;
use LPWork\Frontend\Exceptions\ApplicationAssetManifestMissingException;

/**
 * Represents the application asset manifest reader framework component.
 */
final readonly class ApplicationAssetManifestReader
{
    public const string RELATIVE_PATH = 'public/build/manifest.json';

    /**
     * Creates a new ApplicationAssetManifestReader instance.
     */
    public function __construct(
        private string $basePath,
        private Filesystem $files = new Filesystem(),
    ) {}

    /**
     * Builds or returns read.
     */
    public function read(): ApplicationAssetManifest
    {
        $path = $this->basePath . '/' . self::RELATIVE_PATH;

        if (!$this->files->isFile($path)) {
            throw new ApplicationAssetManifestMissingException($path);
        }

        try {
            $decoded = json_decode($this->files->read($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ApplicationAssetManifestInvalidException($path, $exception);
        }

        if (!is_array($decoded)) {
            throw new ApplicationAssetManifestInvalidException($path);
        }

        return new ApplicationAssetManifest($this->entries($decoded, $path));
    }

    /**
     * @param array<array-key, mixed> $decoded
     *
     * @return array<string, ApplicationAssetManifestEntry>
     */
    private function entries(array $decoded, string $path): array
    {
        $entries = [];

        foreach ($decoded as $sourcePath => $entry) {
            if (!is_string($sourcePath) || !is_array($entry) || !isset($entry['file']) || !is_string($entry['file'])) {
                throw new ApplicationAssetManifestInvalidException($path);
            }

            $entries[$sourcePath] = new ApplicationAssetManifestEntry(
                file: $entry['file'],
                css: $this->css($entry['css'] ?? [], $path),
            );
        }

        return $entries;
    }

    /**
     * @return list<string>
     */
    private function css(mixed $css, string $path): array
    {
        if (!is_array($css)) {
            throw new ApplicationAssetManifestInvalidException($path);
        }

        $files = [];

        foreach ($css as $file) {
            if (!is_string($file)) {
                throw new ApplicationAssetManifestInvalidException($path);
            }

            $files[] = $file;
        }

        return $files;
    }
}
