<?php
declare(strict_types=1);

namespace LPwork\Translation;

use LPwork\Translation\Exception\TranslationLoaderException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds Translator instances using JSON resources and cache pool for catalogues.
 */
class TranslatorFactory
{
    /**
     * @param TranslationConfiguration $config
     * @param CacheItemPoolInterface   $pool
     *
     * @return TranslatorInterface
     */
    public function create(
        TranslationConfiguration $config,
        CacheItemPoolInterface $pool,
    ): TranslatorInterface {
        $translator = new Translator($config->locale());
        $translator->setFallbackLocales([$config->fallbackLocale()]);
        $translator->addLoader("array", new ArrayLoader());

        $path = \rtrim($config->path(), "/");

        if ($path === "" || !\is_dir($path)) {
            throw new TranslationLoaderException(
                \sprintf('Translation path "%s" is not readable.', $path),
            );
        }

        $locales = [$config->locale()];
        $fallback = $config->fallbackLocale();

        if ($fallback !== "" && $fallback !== $config->locale()) {
            $locales[] = $fallback;
        }

        foreach ($locales as $locale) {
            $messages = $this->loadMessages($pool, $config, $path, $locale);
            $translator->addResource("array", $messages, $locale);
        }

        return $translator;
    }

    /**
     * @param CacheItemPoolInterface   $pool
     * @param TranslationConfiguration $config
     * @param string                   $basePath
     * @param string                   $locale
     *
     * @return array<string, string>
     */
    private function loadMessages(
        CacheItemPoolInterface $pool,
        TranslationConfiguration $config,
        string $basePath,
        string $locale,
    ): array {
        $cacheKey = $config->cachePrefix() . $locale;
        $item = $pool->getItem($cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();

            if (\is_array($cached)) {
                /** @var array<string, string> $cached */
                return $cached;
            }
        }

        $messages = $this->loadLocaleFiles($basePath, $locale);
        $item->set($messages);
        $pool->save($item);

        return $messages;
    }

    /**
     * @param string $basePath
     * @param string $locale
     *
     * @return array<string, string>
     */
    private function loadLocaleFiles(string $basePath, string $locale): array
    {
        $directory = $basePath . "/" . $locale;

        if (!\is_dir($directory)) {
            return [];
        }

        $messages = [];

        /** @var array<int, string> $files */
        $files = \glob($directory . "/*.json") ?: [];

        foreach ($files as $file) {
            $content = @\file_get_contents($file);

            if ($content === false) {
                continue;
            }

            $decoded = \json_decode($content, true);

            if (!\is_array($decoded)) {
                continue;
            }

            foreach ($decoded as $key => $value) {
                if (\is_string($key) && \is_string($value)) {
                    $messages[$key] = $value;
                }
            }
        }

        return $messages;
    }
}
