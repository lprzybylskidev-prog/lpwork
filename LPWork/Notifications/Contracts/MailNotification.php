<?php

declare(strict_types=1);

namespace LPWork\Notifications\Contracts;

use LPWork\Mail\MailMessage;

/**
 * Defines the contract for mail notification.
 */
interface MailNotification extends Notification
{
    /**
     * Converts this value to to mail output.
     */
    public function toMail(Notifiable $notifiable): MailMessage;
}
