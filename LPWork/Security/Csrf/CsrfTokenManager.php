<?php

declare(strict_types=1);

namespace LPWork\Security\Csrf;

use LPWork\Security\Exceptions\CsrfTokenGenerationException;
use LPWork\Session\Session;
use Random\RandomException;

/**
 * Coordinates configured csrf token manager services.
 */
final readonly class CsrfTokenManager
{
    /**
     * Creates a new CsrfTokenManager instance.
     */
    public function __construct(private CsrfConfig $config) {}

    /**
     * Converts this value to token output.
     */
    public function token(Session $session): string
    {
        $token = $session->get($this->config->sessionKey());

        if (is_string($token) && $token !== '') {
            return $token;
        }

        $token = $this->generate();
        $session->put($this->config->sessionKey(), $token);

        return $token;
    }

    /**
     * Performs the form token operation.
     */
    public function formToken(Session $session, string $form): string
    {
        if (!$this->config->usesPerFormTokens()) {
            return $this->token($session);
        }

        $tokens = $this->formTokens($session);
        $token = $tokens[$form] ?? null;

        if (is_string($token) && $token !== '') {
            return $token;
        }

        $token = $this->generate();
        $tokens[$form] = $token;
        $session->put($this->formTokensKey(), $tokens);

        return $token;
    }

    /**
     * Reports whether valid.
     */
    public function valid(Session $session, ?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $expected = $this->token($session);

        $valid = hash_equals($expected, $token);

        if ($valid && $this->config->rotates()) {
            $session->put($this->config->sessionKey(), $this->generate());
        }

        return $valid;
    }

    /**
     * Reports whether valid for form.
     */
    public function validForForm(Session $session, string $form, ?string $token): bool
    {
        if (!$this->config->usesPerFormTokens()) {
            return $this->valid($session, $token);
        }

        if ($token === null || $token === '') {
            return false;
        }

        $tokens = $this->formTokens($session);
        $expected = $tokens[$form] ?? null;

        if (!is_string($expected) || $expected === '' || !hash_equals($expected, $token)) {
            return false;
        }

        if ($this->config->rotates()) {
            $tokens[$form] = $this->generate();
            $session->put($this->formTokensKey(), $tokens);
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    private function formTokens(Session $session): array
    {
        $tokens = $session->get($this->formTokensKey(), []);

        if (!is_array($tokens)) {
            return [];
        }

        $strings = [];

        foreach ($tokens as $form => $token) {
            if (is_string($form) && is_string($token) && $token !== '') {
                $strings[$form] = $token;
            }
        }

        return $strings;
    }

    private function formTokensKey(): string
    {
        return $this->config->sessionKey() . '_forms';
    }

    private function generate(): string
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (RandomException $exception) {
            throw CsrfTokenGenerationException::forPrevious($exception);
        }
    }
}
