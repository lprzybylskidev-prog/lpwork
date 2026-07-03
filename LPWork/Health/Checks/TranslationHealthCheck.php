<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\Translation\Translator;

/**
 * Represents the translation health check framework component.
 */
final readonly class TranslationHealthCheck implements HealthCheck
{
    /**
     * Creates a new TranslationHealthCheck instance.
     */
    public function __construct(
        private Translator $translator,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'translation';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $locale = $this->translator->locale();
        $value = $this->translator->get('validation.attributes.email', locale: $locale);

        if ($value === '') {
            return HealthCheckResult::unhealthy($this->name(), sprintf('Translator returned an empty value for locale [%s].', $locale));
        }

        return HealthCheckResult::healthy($this->name(), sprintf('Translator loaded locale [%s] with fallback support.', $locale));
    }
}
