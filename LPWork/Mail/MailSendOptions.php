<?php

declare(strict_types=1);

namespace LPWork\Mail;

/**
 * Carries options for mail send options behavior.
 */
final readonly class MailSendOptions
{
    /**
     * Creates a new MailSendOptions instance.
     */
    public function __construct(
        public ?string $transport = null,
        public ?string $sender = null,
    ) {}
}
