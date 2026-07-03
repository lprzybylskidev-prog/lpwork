<?php

declare(strict_types=1);

namespace Tests\support\testing\Mail;

use LPWork\Mail\Contracts\MailTransport;
use LPWork\Mail\MailMessage;
use PHPUnit\Framework\Assert;

final readonly class MailTransportContract
{
    public function __construct(
        private MailTransport $transport,
        private string $name,
    ) {}

    public function verifiesSendResultBehavior(): void
    {
        $message = MailMessage::create()
            ->from('hello@example.test')
            ->to('ada@example.test')
            ->subject('Contract message')
            ->text('Hello from the contract test.');

        $result = $this->transport->send($message);

        Assert::assertSame($this->name, $result->transport);
        Assert::assertNotSame('', $result->messageId);
    }
}
