<?php

declare(strict_types=1);

use LPWork\Mail\MailAddress;
use LPWork\Mail\MailMessage;
use LPWork\Mail\MailMessageRenderer;
use Tests\support\view\ViewTestEnvironment;

it('wraps html fragments in the default LPWork mail template', function (): void {
    $message = MailMessage::create()
        ->from(new MailAddress('hello@example.test', 'LPWork'))
        ->to(new MailAddress('ada@example.test', 'Ada'))
        ->subject('Welcome')
        ->html('<p>Hello Ada</p>');

    $rendered = new MailMessageRenderer()->render($message, 'message@example.test');
    $encoded = substr($rendered, (int) strpos($rendered, "\r\n\r\n") + 4);
    $html = base64_decode(str_replace("\r\n", '', $encoded), strict: true);

    expect($html)->toBeString()
        ->and($html)->toContain('LPWORK')
        ->and($html)->toContain('Mail')
        ->and($html)->toContain('data:image/svg+xml;base64,')
        ->and($html)->toContain('#090b0f')
        ->and($html)->not->toContain('Default template')
        ->and($html)->not->toContain('Message')
        ->and($html)->toContain('<p>Hello Ada</p>');
});

it('keeps complete application html mail documents unchanged', function (): void {
    $message = MailMessage::create()
        ->from(new MailAddress('hello@example.test', 'LPWork'))
        ->to(new MailAddress('ada@example.test', 'Ada'))
        ->subject('Custom')
        ->html('<html><body><h1>Custom mail</h1></body></html>');

    $rendered = new MailMessageRenderer()->render($message, 'message@example.test');
    $encoded = substr($rendered, (int) strpos($rendered, "\r\n\r\n") + 4);
    $html = base64_decode(str_replace("\r\n", '', $encoded), strict: true);

    expect($html)->toBe('<html><body><h1>Custom mail</h1></body></html>');
});

it('renders html mail through a configured application template view', function (): void {
    $environment = ViewTestEnvironment::create();
    $environment->createView('views/mail/template.php', '<html><body><h1><?= $view->e($subject) ?></h1><?= $body ?></body></html>');
    $message = MailMessage::create()
        ->from(new MailAddress('hello@example.test', 'LPWork'))
        ->to(new MailAddress('ada@example.test', 'Ada'))
        ->subject('Templated')
        ->html('<p>Body</p>');

    try {
        $rendered = new MailMessageRenderer(
            views: $environment->factory(),
            templateView: 'mail.template',
        )->render($message, 'message@example.test');
    } finally {
        $environment->remove();
    }

    $encoded = substr($rendered, (int) strpos($rendered, "\r\n\r\n") + 4);
    $html = base64_decode(str_replace("\r\n", '', $encoded), strict: true);

    expect($html)->toBe('<html><body><h1>Templated</h1><p>Body</p></body></html>');
});
