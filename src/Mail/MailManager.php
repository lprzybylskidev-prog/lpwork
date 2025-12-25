<?php
declare(strict_types=1);

namespace LPwork\Mail;

use LPwork\Mail\Contract\MailManagerInterface;
use LPwork\Mail\Contract\MailerFactoryInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Provides access to configured mailers.
 */
final class MailManager implements MailManagerInterface
{
    /**
     * @var MailConfiguration
     */
    private MailConfiguration $config;

    /**
     * @var MailerFactoryInterface
     */
    private MailerFactoryInterface $factory;

    /**
     * @var array<string, MailerInterface>
     */
    private array $mailers = [];

    /**
     * @param MailConfiguration $config
     * @param MailerFactoryInterface $factory
     */
    public function __construct(MailConfiguration $config, MailerFactoryInterface $factory)
    {
        $this->config = $config;
        $this->factory = $factory;
    }

    /**
     * Returns a mailer for the given connection name.
     *
     * @param string|null $name
     *
     * @return MailerInterface
     */
    public function mailer(?string $name = null): MailerInterface
    {
        $connection = $name ?? $this->config->defaultConnection();

        if (!isset($this->mailers[$connection])) {
            $this->mailers[$connection] = $this->factory->create($this->config, $connection);
        }

        return $this->mailers[$connection];
    }

    /**
     * @return MailerInterface
     */
    public function default(): MailerInterface
    {
        return $this->mailer();
    }
}
