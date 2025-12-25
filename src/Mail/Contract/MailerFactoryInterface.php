<?php
declare(strict_types=1);

namespace LPwork\Mail\Contract;

use LPwork\Mail\MailConfiguration;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Contract for building mailers from configuration.
 */
interface MailerFactoryInterface
{
    /**
     * @param MailConfiguration $configuration
     * @param string            $connection
     *
     * @return MailerInterface
     */
    public function create(MailConfiguration $configuration, string $connection): MailerInterface;
}
