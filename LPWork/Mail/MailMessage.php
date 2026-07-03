<?php

declare(strict_types=1);

namespace LPWork\Mail;

use function array_merge;

use LPWork\Mail\Exceptions\InvalidMailMessageException;

/**
 * Represents the mail message framework component.
 */
final readonly class MailMessage
{
    /**
     * @param list<MailAddress> $to
     * @param array<string, string> $headers
     */
    public function __construct(
        private array $to = [],
        private ?MailAddress $from = null,
        private string $subject = '',
        private ?string $text = null,
        private ?string $html = null,
        private array $headers = [],
    ) {}

    /**
     * Creates a new value for this component.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Converts this value to to output.
     */
    public function to(string|MailAddress $address, ?string $name = null): self
    {
        $recipient = $address instanceof MailAddress ? $address : new MailAddress($address, $name);

        return new self(
            to: [...$this->to, $recipient],
            from: $this->from,
            subject: $this->subject,
            text: $this->text,
            html: $this->html,
            headers: $this->headers,
        );
    }

    /**
     * Creates a MailMessage instance from from input.
     */
    public function from(string|MailAddress $address, ?string $name = null): self
    {
        $sender = $address instanceof MailAddress ? $address : new MailAddress($address, $name);

        return new self($this->to, $sender, $this->subject, $this->text, $this->html, $this->headers);
    }

    /**
     * Returns subject.
     */
    public function subject(string $subject): self
    {
        return new self($this->to, $this->from, $subject, $this->text, $this->html, $this->headers);
    }

    /**
     * Performs the text operation.
     */
    public function text(string $body): self
    {
        return new self($this->to, $this->from, $this->subject, $body, $this->html, $this->headers);
    }

    /**
     * Performs the html operation.
     */
    public function html(string $body): self
    {
        return new self($this->to, $this->from, $this->subject, $this->text, $body, $this->headers);
    }

    /**
     * Performs the header operation.
     */
    public function header(string $name, string $value): self
    {
        return new self(
            to: $this->to,
            from: $this->from,
            subject: $this->subject,
            text: $this->text,
            html: $this->html,
            headers: array_merge($this->headers, [$name => $value]),
        );
    }

    /**
     * Returns a copy with with default from applied.
     */
    public function withDefaultFrom(MailAddress $from): self
    {
        if ($this->from !== null) {
            return $this;
        }

        return new self($this->to, $from, $this->subject, $this->text, $this->html, $this->headers);
    }

    /**
     * Performs assert sendable.
     */
    public function assertSendable(): void
    {
        if ($this->to === []) {
            throw InvalidMailMessageException::missingRecipients();
        }

        if ($this->from === null) {
            throw InvalidMailMessageException::missingSender();
        }

        if ($this->subject === '') {
            throw InvalidMailMessageException::missingSubject();
        }

        if ($this->text === null && $this->html === null) {
            throw InvalidMailMessageException::missingBody();
        }
    }

    /**
     * @return list<MailAddress>
     */
    public function toAddresses(): array
    {
        return $this->to;
    }

    /**
     * Creates a MailMessage instance from from address input.
     */
    public function fromAddress(): ?MailAddress
    {
        return $this->from;
    }

    /**
     * Returns subject line.
     */
    public function subjectLine(): string
    {
        return $this->subject;
    }

    /**
     * Performs the text body operation.
     */
    public function textBody(): ?string
    {
        return $this->text;
    }

    /**
     * Performs the html body operation.
     */
    public function htmlBody(): ?string
    {
        return $this->html;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }
}
