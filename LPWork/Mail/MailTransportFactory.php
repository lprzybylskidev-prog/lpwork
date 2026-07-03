<?php

declare(strict_types=1);

namespace LPWork\Mail;

use function in_array;

use LPWork\Config\ArrayConfigReader;
use LPWork\Logging\Contracts\Logger;
use LPWork\Mail\Contracts\MailTransport;
use LPWork\Mail\Exceptions\InvalidMailConfigException;
use LPWork\Mail\Exceptions\InvalidMailDriverException;
use LPWork\Mail\Exceptions\MissingMailConfigException;
use LPWork\Mail\Transports\LogMailTransport;
use LPWork\Mail\Transports\MailgunMailTransport;
use LPWork\Mail\Transports\SendmailMailTransport;
use LPWork\Mail\Transports\SesMailTransport;
use LPWork\Mail\Transports\SmtpMailTransport;

/**
 * Creates mail transport factory instances from framework configuration.
 */
final readonly class MailTransportFactory
{
    /**
     * Creates a new MailTransportFactory instance.
     */
    public function __construct(
        private Logger $logger,
        private bool $appDebug = false,
        private MailMessageRenderer $renderer = new MailMessageRenderer(),
    ) {}

    /**
     * @param array<array-key, mixed> $config
     */
    public function create(string $name, array $config, string $key): MailTransport
    {
        $reader = $this->reader($config);
        $driver = $reader->string('driver', "{$key}.driver");

        return match ($driver) {
            'log' => new LogMailTransport($name, $this->logger, $this->appDebug),
            'smtp' => new SmtpMailTransport(
                name: $name,
                host: $reader->string('host', "{$key}.host"),
                port: $this->positiveInt($reader, 'port', "{$key}.port"),
                username: $this->optionalNonEmptyString($reader, 'username', "{$key}.username"),
                password: $reader->optionalString('password', "{$key}.password", allowEmpty: true),
                encryption: $this->encryption($reader, "{$key}.encryption"),
                timeoutSeconds: $this->positiveInt($reader, 'timeout_seconds', "{$key}.timeout_seconds"),
                renderer: $this->renderer,
            ),
            'sendmail' => new SendmailMailTransport(
                name: $name,
                command: $reader->optionalString('command', "{$key}.command") ?? '/usr/sbin/sendmail -t -i',
                renderer: $this->renderer,
            ),
            'ses' => new SesMailTransport(
                name: $name,
                region: $reader->string('region', "{$key}.region"),
                accessKey: $reader->string('access_key', "{$key}.access_key"),
                secretKey: $reader->string('secret_key', "{$key}.secret_key"),
                renderer: $this->renderer,
            ),
            'mailgun' => new MailgunMailTransport(
                name: $name,
                domain: $reader->string('domain', "{$key}.domain"),
                secret: $reader->string('secret', "{$key}.secret"),
                endpoint: $reader->optionalString('endpoint', "{$key}.endpoint") ?? 'https://api.mailgun.net',
                renderer: $this->renderer,
            ),
            default => throw new InvalidMailDriverException($driver),
        };
    }

    private function positiveInt(ArrayConfigReader $reader, string $name, string $key): int
    {
        $value = $reader->int($name, $key);

        if ($value <= 0) {
            throw new InvalidMailConfigException($key);
        }

        return $value;
    }

    private function encryption(ArrayConfigReader $reader, string $key): ?string
    {
        $encryption = $this->optionalNonEmptyString($reader, 'encryption', $key);

        if ($encryption !== null && !in_array($encryption, ['tls', 'ssl'], true)) {
            throw new InvalidMailConfigException($key);
        }

        return $encryption;
    }

    private function optionalNonEmptyString(ArrayConfigReader $reader, string $name, string $key): ?string
    {
        $value = $reader->optionalString($name, $key, allowEmpty: true);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function reader(array $config): ArrayConfigReader
    {
        return new ArrayConfigReader(
            config: $config,
            missingException: static fn(string $key): MissingMailConfigException => new MissingMailConfigException($key),
            invalidException: static fn(string $key): InvalidMailConfigException => new InvalidMailConfigException($key),
        );
    }
}
