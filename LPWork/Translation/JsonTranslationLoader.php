<?php

declare(strict_types=1);

namespace LPWork\Translation;

use function json_decode;

use const JSON_THROW_ON_ERROR;

use JsonException;
use LPWork\Filesystem\Exceptions\FileNotFoundException;
use LPWork\Filesystem\Filesystem;
use LPWork\Translation\Exceptions\InvalidTranslationFileException;

/**
 * Represents the json translation loader framework component.
 */
final readonly class JsonTranslationLoader
{
    /**
     * Creates a new JsonTranslationLoader instance.
     */
    public function __construct(
        private string $path,
        private ?TranslationCache $cache = null,
        private ?TranslationNamespaceRegistry $namespaces = null,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * @return array<string, string>
     */
    public function load(string $locale): array
    {
        $cached = $this->cache?->load($locale);

        if ($cached !== null) {
            return $cached;
        }

        return [
            ...$this->loadPath($this->path, $locale),
            ...$this->loadNamespaces($locale),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function loadNamespaces(string $locale): array
    {
        $translations = [];

        foreach ($this->namespaces?->all() ?? [] as $namespace => $path) {
            foreach ($this->loadPath($path, $locale) as $key => $value) {
                $translations[$namespace . '::' . $key] = $value;
            }
        }

        return $translations;
    }

    /**
     * @return array<string, string>
     */
    private function loadPath(string $basePath, string $locale): array
    {
        $path = $basePath . '/' . $locale . '.json';

        try {
            $contents = $this->filesystem->read($path);
            $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (FileNotFoundException) {
            return [];
        } catch (JsonException) {
            throw new InvalidTranslationFileException($path);
        }

        if (!is_array($decoded)) {
            throw new InvalidTranslationFileException($path);
        }

        $translations = [];

        foreach ($decoded as $key => $value) {
            if (!is_string($key) || $key === '' || !is_string($value)) {
                throw new InvalidTranslationFileException($path);
            }

            $translations[$key] = $value;
        }

        return $translations;
    }
}
