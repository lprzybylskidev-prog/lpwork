<?php
declare(strict_types=1);

namespace LPwork\Mail;

use LPwork\Mail\Contract\MailerFactoryInterface;
use LPwork\Mail\Exception\MailConfigurationException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;

/**
 * Builds mailers for configured transports.
 */
final class MailerFactory implements MailerFactoryInterface
{
    /**
     * Creates a mailer from a connection name.
     *
     * @param MailConfiguration $config
     * @param string            $name
     *
     * @return MailerInterface
     */
    public function create(MailConfiguration $config, string $name): MailerInterface
    {
        $connection = $config->connection($name);
        $dsn = (string) ($connection['dsn'] ?? '');

        if ($dsn === '') {
            throw new MailConfigurationException(
                \sprintf('Mail connection "%s" is missing DSN.', $name),
            );
        }

        return new Mailer(Transport::fromDsn($dsn));
    }
}
