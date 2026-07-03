<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\Security\Csrf\CsrfTokenManager;
use LPWork\Session\Session;
use LPWork\Url\UrlGenerator;

/**
 * Represents the security health check framework component.
 */
final readonly class SecurityHealthCheck implements HealthCheck
{
    /**
     * Creates a new SecurityHealthCheck instance.
     */
    public function __construct(
        private CsrfTokenManager $csrf,
        private UrlGenerator $url,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'security';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $session = new Session();
        $token = $this->csrf->token($session);

        if (!$this->csrf->valid($session, $token)) {
            return HealthCheckResult::unhealthy($this->name(), 'CSRF token manager did not validate its generated token.');
        }

        $signed = $this->url->signedTo('/health', absolute: false);

        if (!str_contains($signed, 'signature=')) {
            return HealthCheckResult::unhealthy($this->name(), 'Signed URL generator did not add a signature.');
        }

        return HealthCheckResult::healthy($this->name(), 'CSRF tokens and signed URLs can be generated and validated.');
    }
}
