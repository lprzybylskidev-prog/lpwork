<?php

declare(strict_types=1);

namespace LPWork\Mail\Transports;

use function bin2hex;

use LPWork\Logging\Contracts\Logger;
use LPWork\Mail\Contracts\MailTransport;
use LPWork\Mail\MailMessage;
use LPWork\Mail\MailSendResult;

use function random_bytes;

/**
 * Represents the log mail transport framework component.
 */
final readonly class LogMailTransport implements MailTransport
{
    /**
     * Creates a new LogMailTransport instance.
     */
    public function __construct(
        private string $name,
        private Logger $logger,
        private bool $appDebug = false,
    ) {}

    /**
     * Runs send.
     */
    public function send(MailMessage $message): MailSendResult
    {
        $messageId = $this->messageId();
        $this->logger->info('Mail message accepted by log transport.', $this->context($message, $messageId));

        return new MailSendResult($this->name, $messageId);
    }

    /**
     * @return array<string, mixed>
     */
    private function context(MailMessage $message, string $messageId): array
    {
        $context = [
            'transport' => $this->name,
            'message_id' => $messageId,
            'recipient_count' => count($message->toAddresses()),
        ];

        if ($this->appDebug) {
            $context['subject'] = $message->subjectLine();
            $context['from'] = $message->fromAddress()?->address();
            $context['to'] = array_map(
                static fn($address): string => $address->address(),
                $message->toAddresses(),
            );
        }

        return $context;
    }

    private function messageId(): string
    {
        return bin2hex(random_bytes(16)) . '@lpwork.local';
    }
}
