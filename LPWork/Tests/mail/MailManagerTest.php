<?php

declare(strict_types=1);

use LPWork\Logging\Enums\LogLevel;
use LPWork\Logging\LogChannel;
use LPWork\Mail\Exceptions\InvalidMailAddressException;
use LPWork\Mail\Exceptions\InvalidMailConfigException;
use LPWork\Mail\Exceptions\InvalidMailDriverException;
use LPWork\Mail\Exceptions\InvalidMailMessageException;
use LPWork\Mail\Exceptions\InvalidMailSenderException;
use LPWork\Mail\Exceptions\InvalidMailTransportException;
use LPWork\Mail\MailAddress;
use LPWork\Mail\MailMessage;
use LPWork\Mail\MailSendOptions;
use LPWork\Mail\MailTransportFactory;
use LPWork\Mail\Transports\SmtpMailTransport;
use Tests\support\logging\InMemoryLogDriver;
use Tests\support\mail\MailTestFactory;

it('sends mail through the default transport and sender', function (): void {
    [$manager, $driver] = MailTestFactory::manager(appDebug: true);

    $result = $manager->send(
        $manager->message()
            ->to('ada@example.test', 'Ada')
            ->subject('Welcome')
            ->text('Hello Ada'),
    );

    expect($result->transport)->toBe('log')
        ->and($manager->transportNames())->toBe(['log', 'smtp'])
        ->and($manager->senderNames())->toBe(['app', 'support'])
        ->and($driver->records)->toHaveCount(2)
        ->and($driver->records[0]->message)->toBe('Mail message accepted by log transport.')
        ->and($driver->records[0]->context['transport'])->toBe('log')
        ->and($driver->records[0]->context['from'])->toBe('hello@example.test')
        ->and($driver->records[0]->context['to'])->toBe(['ada@example.test'])
        ->and($driver->records[1]->message)->toBe('Mail message sent.')
        ->and($driver->records[1]->level)->toBe(LogLevel::Info);
});

it('selects explicit sender identities independently from transports', function (): void {
    [$manager, $driver] = MailTestFactory::manager(appDebug: true);

    $manager->send(
        MailMessage::create()
            ->to('ada@example.test')
            ->subject('Support reply')
            ->html('<p>Hello</p>'),
        new MailSendOptions(transport: 'log', sender: 'support'),
    );

    expect($driver->records[0]->context['from'])->toBe('support@example.test')
        ->and($driver->records[0]->context['subject'])->toBe('Support reply');
});

it('keeps mail logs free of addresses and subject outside app debug', function (): void {
    [$manager, $driver] = MailTestFactory::manager(appDebug: false);

    $manager->send(
        MailMessage::create()
            ->to('ada@example.test')
            ->subject('Private subject')
            ->text('Private body'),
    );

    expect($driver->records[0]->context)->toHaveKeys(['transport', 'message_id', 'recipient_count'])
        ->and($driver->records[0]->context)->not->toHaveKeys(['subject', 'from', 'to'])
        ->and($driver->records[1]->context)->toHaveKeys(['transport', 'message_id', 'recipient_count'])
        ->and($driver->records[1]->context)->not->toHaveKeys(['subject', 'from', 'to']);
});

it('validates mail messages before sending', function (): void {
    [$manager] = MailTestFactory::manager();

    expect(fn() => $manager->send(MailMessage::create()->subject('Missing recipient')->text('Body')))
        ->toThrow(InvalidMailMessageException::class, 'requires at least one recipient')
        ->and(fn() => $manager->send(MailMessage::create()->to('ada@example.test')->text('Body')))
        ->toThrow(InvalidMailMessageException::class, 'requires a subject')
        ->and(fn() => $manager->send(MailMessage::create()->to('ada@example.test')->subject('Missing body')))
        ->toThrow(InvalidMailMessageException::class, 'requires a text or HTML body');
});

it('resolves and validates named senders and transports explicitly', function (): void {
    [$manager] = MailTestFactory::manager();

    expect($manager->sender('support'))->toBeInstanceOf(MailAddress::class)
        ->and($manager->sender('support')->address())->toBe('support@example.test')
        ->and(fn() => new MailAddress('ada@example.test', "Ada\nInjected"))
        ->toThrow(InvalidMailAddressException::class)
        ->and(fn() => $manager->sender('missing'))->toThrow(InvalidMailSenderException::class)
        ->and(fn() => $manager->transport('missing'))->toThrow(InvalidMailTransportException::class);
});

it('creates smtp transports from explicit transport config', function (): void {
    [$manager] = MailTestFactory::manager();

    expect($manager->transport('smtp'))->toBeInstanceOf(SmtpMailTransport::class);
});

it('rejects unsupported mail drivers and invalid smtp options', function (): void {
    $driver = new InMemoryLogDriver();
    $logger = new LogChannel('mail', $driver);
    $factory = new MailTransportFactory($logger);

    expect(fn() => $factory->create('bad', ['driver' => 'unknown'], 'transports.bad'))
        ->toThrow(InvalidMailDriverException::class)
        ->and(fn() => $factory->create('smtp', [
            'driver' => 'smtp',
            'host' => 'smtp.example.test',
            'port' => 0,
            'timeout_seconds' => 30,
        ], 'transports.smtp'))->toThrow(InvalidMailConfigException::class)
        ->and(fn() => $factory->create('smtp', [
            'driver' => 'smtp',
            'host' => 'smtp.example.test',
            'port' => 587,
            'encryption' => 'starttls',
            'timeout_seconds' => 30,
        ], 'transports.smtp'))->toThrow(InvalidMailConfigException::class);
});
