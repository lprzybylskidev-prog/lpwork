<?php

declare(strict_types=1);

namespace LPWork\Mail;

use LPWork\Config\ArrayConfigReader;
use LPWork\Logging\Contracts\Logger;
use LPWork\Logging\Enums\LogLevel;
use LPWork\Mail\Contracts\MailTransport;
use LPWork\Mail\Exceptions\InvalidMailConfigException;
use LPWork\Mail\Exceptions\InvalidMailSenderException;
use LPWork\Mail\Exceptions\InvalidMailTransportException;
use LPWork\Mail\Exceptions\MissingMailConfigException;

/**
 * Resolves mail transports and senders, builds messages, and sends mail.
 */
final class MailManager
{
    /**
     * @var array<string, MailTransport>
     */
    private array $transports = [];

    private ArrayConfigReader $reader;

    /**
     * @param array<array-key, mixed> $config
     */
    public function __construct(
        private readonly array $config,
        private readonly MailTransportFactory $transportFactory,
        private readonly ?Logger $logger = null,
        private readonly bool $appDebug = false,
    ) {
        $this->reader = $this->reader($config);
    }

    /**
     * Creates a new fluent mail message builder.
     */
    public function message(): MailMessage
    {
        return MailMessage::create();
    }

    /**
     * Returns the configured default mail transport.
     */
    public function default(): MailTransport
    {
        return $this->transport($this->defaultTransportName());
    }

    /**
     * Returns the transport name used when send options omit one.
     */
    public function defaultTransportName(): string
    {
        return $this->reader->string('default');
    }

    /**
     * Returns the sender name used when a message has no explicit from address.
     */
    public function defaultSenderName(): string
    {
        return $this->reader->string('default_sender');
    }

    /**
     * Returns a named mail transport, creating and caching it on first use.
     */
    public function transport(string $name): MailTransport
    {
        if (array_key_exists($name, $this->transports)) {
            return $this->transports[$name];
        }

        $transports = $this->reader->arrayMap('transports');

        if (!array_key_exists($name, $transports)) {
            throw new InvalidMailTransportException($name);
        }

        $this->transports[$name] = $this->transportFactory->create($name, $transports[$name], "transports.{$name}");

        return $this->transports[$name];
    }

    /**
     * Returns a configured sender address by name.
     */
    public function sender(string $name): MailAddress
    {
        $senders = $this->reader->arrayMap('senders');

        if (!array_key_exists($name, $senders)) {
            throw new InvalidMailSenderException($name);
        }

        $reader = $this->reader($senders[$name]);

        return new MailAddress(
            address: $reader->string('address', "senders.{$name}.address"),
            name: $reader->optionalString('name', "senders.{$name}.name", allowEmpty: true),
        );
    }

    /**
     * Sends a message through the selected transport after applying the default sender.
     */
    public function send(MailMessage $message, ?MailSendOptions $options = null): MailSendResult
    {
        $options ??= new MailSendOptions();
        $transportName = $options->transport ?? $this->defaultTransportName();
        $senderName = $options->sender ?? $this->defaultSenderName();
        $message = $message->withDefaultFrom($this->sender($senderName));
        $message->assertSendable();

        $result = $this->transport($transportName)->send($message);
        $this->logSentMessage($message, $result);

        return $result;
    }

    /**
     * Returns all configured mail transport names.
     *
     * @return list<string>
     */
    public function transportNames(): array
    {
        return array_keys($this->reader->arrayMap('transports'));
    }

    /**
     * Returns all configured sender names.
     *
     * @return list<string>
     */
    public function senderNames(): array
    {
        return array_keys($this->reader->arrayMap('senders'));
    }

    private function logSentMessage(MailMessage $message, MailSendResult $result): void
    {
        if ($this->logger === null || !$this->loggingReader()->bool('enabled', 'logging.enabled')) {
            return;
        }

        $level = LogLevel::tryFrom($this->loggingReader()->string('level', 'logging.level')) ?? LogLevel::Info;
        $context = [
            'transport' => $result->transport,
            'message_id' => $result->messageId,
            'recipient_count' => count($message->toAddresses()),
        ];

        if ($this->appDebug) {
            $context['subject'] = $message->subjectLine();
            $context['from'] = $message->fromAddress()?->address();
            $context['to'] = array_map(
                static fn(MailAddress $address): string => $address->address(),
                $message->toAddresses(),
            );
        }

        $this->logger->log($level, 'Mail message sent.', $context);
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

    private function loggingReader(): ArrayConfigReader
    {
        return $this->reader($this->reader->array('logging'));
    }
}
