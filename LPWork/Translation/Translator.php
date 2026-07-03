<?php

declare(strict_types=1);

namespace LPWork\Translation;

use function array_key_exists;

use Stringable;

/**
 * Represents the translator framework component.
 */
final class Translator
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $loaded = [];

    /**
     * Creates a new Translator instance.
     */
    public function __construct(
        private readonly JsonTranslationLoader $loader,
        private string $locale = 'en_US',
        private readonly string $fallbackLocale = 'en_US',
        private readonly TranslationParameterFormatter $parameters = new TranslationParameterFormatter(),
    ) {}

    /**
     * Performs the locale operation.
     */
    public function locale(): string
    {
        return $this->locale;
    }

    /**
     * Registers or stores set locale.
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @param array<string, scalar|Stringable|null> $parameters
     */
    public function get(string $key, array $parameters = [], ?string $locale = null): string
    {
        return $this->translate($key, $parameters, $locale);
    }

    /**
     * @param array<string, scalar|Stringable|null> $parameters
     */
    public function text(string $text, array $parameters = [], ?string $locale = null): string
    {
        return $this->translate($text, $parameters, $locale);
    }

    /**
     * @param array<string, scalar|Stringable|null> $parameters
     */
    private function translate(string $key, array $parameters, ?string $locale): string
    {
        $locale ??= $this->locale;
        $line = $this->line($key, $locale) ?? $this->line($key, $this->fallbackLocale) ?? $key;

        return $this->parameters->replace(
            $line,
            $parameters,
            fn(string $value): ?string => $this->translatedParameterValue($value, $locale),
        );
    }

    private function line(string $key, string $locale): ?string
    {
        $translations = $this->translations($locale);

        return array_key_exists($key, $translations) ? $translations[$key] : null;
    }

    /**
     * @return array<string, string>
     */
    private function translations(string $locale): array
    {
        if (!array_key_exists($locale, $this->loaded)) {
            $this->loaded[$locale] = $this->loader->load($locale);
        }

        return $this->loaded[$locale];
    }

    private function translatedParameterValue(string $value, string $locale): ?string
    {
        if ($value === '') {
            return null;
        }

        return $this->line('validation.attributes.' . $value, $locale)
            ?? $this->line('validation.attributes.' . $value, $this->fallbackLocale);
    }
}
