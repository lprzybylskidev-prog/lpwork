<?php
declare(strict_types=1);

namespace LPwork\Security\Csrf;

use LPwork\Http\Session\Contract\SessionInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

/**
 * Stores CSRF tokens in LPwork session.
 */
final class CsrfTokenSessionStorage implements TokenStorageInterface
{
    /**
     * @var string
     */
    private const KEY = 'csrf_tokens';

    /**
     * @var SessionInterface
     */
    private SessionInterface $session;

    /**
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @inheritDoc
     */
    public function getToken(string $tokenId): string
    {
        /** @var array<string, string> $all */
        $all = (array) $this->session->get(self::KEY, []);

        return $all[$tokenId] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function setToken(string $tokenId, string $token): void
    {
        /** @var array<string, string> $all */
        $all = (array) $this->session->get(self::KEY, []);
        $all[$tokenId] = $token;
        $this->session = $this->session->with(self::KEY, $all);
    }

    /**
     * @inheritDoc
     */
    public function removeToken(string $tokenId): ?string
    {
        /** @var array<string, string> $all */
        $all = (array) $this->session->get(self::KEY, []);
        $previous = $all[$tokenId] ?? null;
        unset($all[$tokenId]);
        $this->session = $this->session->with(self::KEY, $all);

        return $previous;
    }

    /**
     * @inheritDoc
     */
    public function hasToken(string $tokenId): bool
    {
        /** @var array<string, string> $all */
        $all = (array) $this->session->get(self::KEY, []);

        return isset($all[$tokenId]);
    }
}
