<?php

declare(strict_types=1);

namespace LPWork\Mail\Transports;

use LPWork\Mail\Contracts\MailTransport;
use LPWork\Mail\Exceptions\MailTransportException;
use LPWork\Mail\MailMessage;
use LPWork\Mail\MailMessageRenderer;
use LPWork\Mail\MailSendResult;
use LPWork\Shared\Http\HttpClient;
use Throwable;

/**
 * Represents the mailgun mail transport framework component.
 */
final readonly class MailgunMailTransport implements MailTransport
{
    /**
     * Creates a new MailgunMailTransport instance.
     */
    public function __construct(
        private string $name,
        private string $domain,
        private string $secret,
        private string $endpoint = 'https://api.mailgun.net',
        private MailMessageRenderer $renderer = new MailMessageRenderer(),
        private HttpClient $http = new HttpClient(),
    ) {}

    /**
     * Runs send.
     */
    public function send(MailMessage $message): MailSendResult
    {
        $messageId = bin2hex(random_bytes(16)) . '@lpwork.local';
        $body = http_build_query([
            'to' => implode(',', array_map(static fn($address): string => $address->formatted(), $message->toAddresses())),
            'message' => $this->renderer->render($message, $messageId),
        ]);

        try {
            $response = $this->http->request('POST', rtrim($this->endpoint, '/') . '/v3/' . $this->domain . '/messages.mime', [
                'Authorization' => 'Basic ' . base64_encode('api:' . $this->secret),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ], $body);
        } catch (Throwable $throwable) {
            throw MailTransportException::sendFailed($this->name, $throwable);
        }

        if (!$response->successful()) {
            throw MailTransportException::unexpectedResponse($this->name, $response->body);
        }

        return new MailSendResult($this->name, $messageId);
    }
}
