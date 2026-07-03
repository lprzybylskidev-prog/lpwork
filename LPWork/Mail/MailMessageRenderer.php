<?php

declare(strict_types=1);

namespace LPWork\Mail;

use function base64_encode;
use function implode;

use LPWork\Frontend\FrameworkMailTemplate;
use LPWork\View\ViewFactory;

use function preg_replace;
use function str_replace;
use function wordwrap;

/**
 * Renders mail message renderer output.
 */
final readonly class MailMessageRenderer
{
    /**
     * Creates a new MailMessageRenderer instance.
     */
    public function __construct(
        private FrameworkMailTemplate $template = new FrameworkMailTemplate(),
        private ?ViewFactory $views = null,
        private ?string $templateView = null,
    ) {}

    /**
     * Renders this component into its output representation.
     */
    public function render(MailMessage $message, string $messageId): string
    {
        $headers = $this->headers($message, $messageId);
        $body = $this->body($message, $messageId);

        return implode("\r\n", [...$headers, '', $body]);
    }

    /**
     * @return list<string>
     */
    private function headers(MailMessage $message, string $messageId): array
    {
        $from = $message->fromAddress();
        $headers = [
            'Message-ID: <' . $this->headerValue($messageId) . '>',
            'From: ' . ($from?->formatted() ?? ''),
            'To: ' . implode(', ', array_map(
                static fn(MailAddress $address): string => $address->formatted(),
                $message->toAddresses(),
            )),
            'Subject: ' . $this->headerValue($message->subjectLine()),
            'MIME-Version: 1.0',
        ];

        foreach ($message->headers() as $name => $value) {
            $headers[] = $this->headerName($name) . ': ' . $this->headerValue($value);
        }

        if ($message->htmlBody() !== null && $message->textBody() !== null) {
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $this->boundary($messageId) . '"';

            return $headers;
        }

        $contentType = $message->htmlBody() === null ? 'text/plain' : 'text/html';
        $headers[] = 'Content-Type: ' . $contentType . '; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';

        return $headers;
    }

    private function body(MailMessage $message, string $messageId): string
    {
        $text = $message->textBody();
        $html = $message->htmlBody();

        if ($html !== null) {
            $html = $this->renderHtml($message->subjectLine(), $html);
        }

        if ($html !== null && $text !== null) {
            $boundary = $this->boundary($messageId);

            return implode("\r\n", [
                '--' . $boundary,
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
                '',
                $this->encodedBody($text),
                '--' . $boundary,
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
                '',
                $this->encodedBody($html),
                '--' . $boundary . '--',
            ]);
        }

        return $this->encodedBody($html ?? $text ?? '');
    }

    private function encodedBody(string $body): string
    {
        return wordwrap(base64_encode($body), 76, "\r\n", true);
    }

    private function renderHtml(string $subject, string $body): string
    {
        if ($this->templateView !== null && $this->views !== null) {
            return $this->views->render($this->templateView, [
                'subject' => $subject,
                'body' => $body,
            ]);
        }

        return $this->template->render($subject, $body);
    }

    private function boundary(string $value): string
    {
        return 'lpwork_' . str_replace(['@', '.', '-'], '_', $value);
    }

    private function headerName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9-]/', '', $name);

        return $name === null || $name === '' ? 'X-LPWork-Mail' : $name;
    }

    private function headerValue(string $value): string
    {
        return str_replace(["\r", "\n"], '', $value);
    }
}
