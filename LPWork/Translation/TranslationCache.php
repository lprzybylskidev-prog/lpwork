<?php

declare(strict_types=1);

namespace LPWork\Translation;

use function basename;
use function is_array;
use function is_string;

use LPWork\Filesystem\Filesystem;
use LPWork\Translation\Exceptions\InvalidTranslationFileException;

use function ltrim;
use function pathinfo;

use const PATHINFO_FILENAME;

use function rtrim;
use function str_starts_with;
use function var_export;

/**
 * Represents the translation cache framework component.
 */
final readonly class TranslationCache
{
    /**
     * Creates a new TranslationCache instance.
     */
    public function __construct(
        private string $basePath,
        private string $translationPath = 'App/Shared/lang',
        private string $path = 'storage/framework/cache/translations.php',
        private ?TranslationNamespaceRegistry $namespaces = null,
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * Returns path.
     */
    public function path(): string
    {
        if (str_starts_with($this->path, '/')) {
            return $this->path;
        }

        return rtrim($this->basePath, '/') . '/' . ltrim($this->path, '/');
    }

    /**
     * Reports whether exists.
     */
    public function exists(): bool
    {
        return $this->filesystem->isFile($this->path());
    }

    /**
     * Clears the state owned by this component.
     */
    public function clear(): void
    {
        $this->filesystem->delete($this->path());
    }

    /**
     * Registers or stores write.
     */
    public function write(): void
    {
        $this->filesystem->write(
            $this->path(),
            "<?php\n\n"
            . "declare(strict_types=1);\n\n"
            . 'return ' . var_export($this->translations(), true) . ";\n",
        );
    }

    /**
     * @return array<string, string>|null
     */
    public function load(string $locale): ?array
    {
        if (!$this->exists()) {
            return null;
        }

        $cached = include $this->path();

        if (!is_array($cached)) {
            throw new InvalidTranslationFileException($this->path());
        }

        $translations = $cached[$locale] ?? [];

        if (!is_array($translations)) {
            throw new InvalidTranslationFileException($this->path());
        }

        return $this->stringMap($translations);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function translations(): array
    {
        $translations = [];
        $basePath = rtrim($this->basePath, '/') . '/' . ltrim($this->translationPath, '/');

        foreach ($this->locales($basePath) as $locale) {
            $translations[$locale] = new JsonTranslationLoader(
                $basePath,
                namespaces: $this->namespaces,
                filesystem: $this->filesystem,
            )->load($locale);
        }

        return $translations;
    }

    /**
     * @return list<string>
     */
    private function locales(string $basePath): array
    {
        $locales = [];

        foreach ($this->translationPaths($basePath) as $path) {
            foreach ($this->filesystem->files($path . '/*.json') as $file) {
                $locale = pathinfo(basename($file), PATHINFO_FILENAME);

                if ($locale === '') {
                    throw new InvalidTranslationFileException($file);
                }

                $locales[$locale] = $locale;
            }
        }

        return array_values($locales);
    }

    /**
     * @return list<string>
     */
    private function translationPaths(string $basePath): array
    {
        return [
            $basePath,
            ...array_values($this->namespaces?->all() ?? []),
        ];
    }

    /**
     * @param array<array-key, mixed> $values
     * @return array<string, string>
     */
    private function stringMap(array $values): array
    {
        $translations = [];

        foreach ($values as $key => $value) {
            if (!is_string($key) || $key === '' || !is_string($value)) {
                throw new InvalidTranslationFileException($this->path());
            }

            $translations[$key] = $value;
        }

        return $translations;
    }
}
