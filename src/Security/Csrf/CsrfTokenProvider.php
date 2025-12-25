<?php
declare(strict_types=1);

namespace LPwork\Security\Csrf;

use LPwork\Http\Session\SessionManager;
use LPwork\Security\SecurityConfiguration;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Provides access to CSRF tokens for application code.
 */
final class CsrfTokenProvider
{
    /**
     * @var SessionManager
     */
    private SessionManager $sessionManager;

    /**
     * @var SecurityConfiguration
     */
    private SecurityConfiguration $config;

    /**
     * @param SessionManager        $sessionManager
     * @param SecurityConfiguration $config
     */
    public function __construct(SessionManager $sessionManager, SecurityConfiguration $config)
    {
        $this->sessionManager = $sessionManager;
        $this->config = $config;
    }

    /**
     * Returns CSRF token string for given token ID (default from config).
     *
     * @param string|null $tokenId
     *
     * @return string
     */
    public function getToken(?string $tokenId = null): string
    {
        $manager = $this->buildManager();
        $id = $tokenId ?? $this->config->csrfTokenId();
        $token = $manager->getToken($id);

        return $token->getValue();
    }

    /**
     * @return CsrfTokenManagerInterface
     */
    private function buildManager(): CsrfTokenManagerInterface
    {
        $session = $this->sessionManager->current();
        $storage = new CsrfTokenSessionStorage($session);

        return new CsrfTokenManager(null, $storage);
    }
}
