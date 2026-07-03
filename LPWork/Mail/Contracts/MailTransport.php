<?php

declare(strict_types=1);

namespace LPWork\Mail\Contracts;

use LPWork\Mail\MailMessage;
use LPWork\Mail\MailSendResult;

/**
 * Defines the contract for mail transport.
 */
interface MailTransport
{
    /**
     * Runs send.
     */
    public function send(MailMessage $message): MailSendResult;
}
