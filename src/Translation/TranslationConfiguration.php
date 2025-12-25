<?php
declare(strict_types=1);

namespace LPwork\Translation;

use LPwork\Config\Support\ConfigNormalizer;

/**
 * Typed translation configuration holder.
 */
final class TranslationConfiguration
{
    use ConfigNormalizer;

    /**
     * @var string
     */
    private string $locale;

    /**
     * @var string
     */
    private string $fallbackLocale;

    /**
     * @var string
     */
    private string $path;

    /**
     * @var string
     */
    private string $cachePool;

    /**
     * @var string
     */
    private string $cachePrefix;

    /**
     * @var bool
     */
    private bool $cacheEnabled;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->locale = $this->stringVal(
            $config['locale'] ?? null,
            'translation.locale',
            'en',
            false,
        );
        $this->fallbackLocale = $this->stringVal(
            $config['fallback_locale'] ?? null,
            'translation.fallback_locale',
            'en',
            false,
        );
        $this->path = $this->stringVal($config['path'] ?? null, 'translation.path', '', true);
        $this->cachePool = $this->stringVal(
            $config['cache_pool'] ?? null,
            'translation.cache_pool',
            'filesystem',
            false,
        );
        $this->cachePrefix = $this->stringVal(
            $config['cache_prefix'] ?? null,
            'translation.cache_prefix',
            'translations:',
            true,
        );
        $this->cacheEnabled = $this->boolVal(
            $config['cache_enabled'] ?? null,
            'translation.cache_enabled',
            true,
        );

        if ($this->cacheEnabled && $this->cachePool === '') {
            throw new \RuntimeException(
                'Translation cache pool must be set when cache is enabled.',
            );
        }
    }

    /**
     * @return string
     */
    public function locale(): string
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function fallbackLocale(): string
    {
        return $this->fallbackLocale;
    }

    /**
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function cachePool(): string
    {
        return $this->cachePool;
    }

    /**
     * @return string
     */
    public function cachePrefix(): string
    {
        return $this->cachePrefix;
    }

    /**
     * @return bool
     */
    public function cacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }
}
