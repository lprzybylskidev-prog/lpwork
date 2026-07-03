<?php

declare(strict_types=1);

namespace LPWork\Mail\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid mail message exception failures.
 */
final class InvalidMailMessageException extends InvalidArgumentException
{
    /**
     * Reports whether missing recipients.
     */
    public static function missingRecipients(): self
    {
        return new self('Mail message requires at least one recipient.');
    }

    /**
     * Reports whether missing sender.
     */
    public static function missingSender(): self
    {
        return new self('Mail message requires a sender.');
    }

    /**
     * Reports whether missing subject.
     */
    public static function missingSubject(): self
    {
        return new self('Mail message requires a subject.');
    }

    /**
     * Reports whether missing body.
     */
    public static function missingBody(): self
    {
        return new self('Mail message requires a text or HTML body.');
    }
}
