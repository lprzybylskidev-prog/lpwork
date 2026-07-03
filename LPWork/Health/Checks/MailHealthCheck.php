<?php

declare(strict_types=1);

namespace LPWork\Health\Checks;

use LPWork\Health\Contracts\HealthCheck;
use LPWork\Health\HealthCheckResult;
use LPWork\Mail\MailManager;

/**
 * Represents the mail health check framework component.
 */
final readonly class MailHealthCheck implements HealthCheck
{
    /**
     * Creates a new MailHealthCheck instance.
     */
    public function __construct(
        private MailManager $mail,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'mail';
    }

    /**
     * Performs the check operation.
     */
    public function check(): HealthCheckResult
    {
        $transport = $this->mail->defaultTransportName();
        $this->mail->default();
        $sender = $this->mail->defaultSenderName();
        $this->mail->sender($sender);

        return HealthCheckResult::healthy($this->name(), sprintf('Mail transport [%s] and sender [%s] are configured and resolvable.', $transport, $sender));
    }
}
