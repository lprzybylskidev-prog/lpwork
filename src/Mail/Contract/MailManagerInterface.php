<?php
declare(strict_types=1);

namespace LPwork\Mail\Contract;

use Symfony\Component\Mailer\MailerInterface;

/**
 * Contract for resolving mailers.
 */
interface MailManagerInterface
{
    /**
     * @param string|null $name
     *
     * @return MailerInterface
     */
    public function mailer(?string $name = null): MailerInterface;

    /**
     * @return MailerInterface
     */
    public function default(): MailerInterface;
}
