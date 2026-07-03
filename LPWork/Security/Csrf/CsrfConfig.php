<?php

declare(strict_types=1);

namespace LPWork\Security\Csrf;

/**
 * Represents csrf config configuration.
 */
final readonly class CsrfConfig
{
    /**
     * Creates a new CsrfConfig instance.
     */
    public function __construct(
        private bool $enabled,
        private string $sessionKey,
        private string $inputKey,
        private string $headerName,
        private bool $rotate,
        private bool $perForm,
    ) {}

    /**
     * Performs the enabled operation.
     */
    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Returns session key.
     */
    public function sessionKey(): string
    {
        return $this->sessionKey;
    }

    /**
     * Returns input key.
     */
    public function inputKey(): string
    {
        return $this->inputKey;
    }

    /**
     * Performs the header name operation.
     */
    public function headerName(): string
    {
        return $this->headerName;
    }

    /**
     * Performs the rotates operation.
     */
    public function rotates(): bool
    {
        return $this->rotate;
    }

    /**
     * Reports whether uses per form tokens.
     */
    public function usesPerFormTokens(): bool
    {
        return $this->perForm;
    }
}
