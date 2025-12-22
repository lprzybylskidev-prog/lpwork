<?php
declare(strict_types=1);

namespace LPwork\Translation;

/**
 * Typed translation configuration holder.
 */
final class TranslationConfiguration
{
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
        $this->locale = (string) ($config["locale"] ?? "en");
        $this->fallbackLocale = (string) ($config["fallback_locale"] ?? "en");
        $this->path = (string) ($config["path"] ?? "");
        $this->cachePool = (string) ($config["cache_pool"] ?? "filesystem");
        $this->cachePrefix =
            (string) ($config["cache_prefix"] ?? "translations:");
        $this->cacheEnabled = (bool) ($config["cache_enabled"] ?? true);
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
