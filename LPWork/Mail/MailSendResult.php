<?php

declare(strict_types=1);

namespace LPWork\Mail;

/**
 * Represents the result of mail send result work.
 */
final readonly class MailSendResult
{
    /**
     * Creates a new MailSendResult instance.
     */
    public function __construct(
        public string $transport,
        public string $messageId,
    ) {}
}
