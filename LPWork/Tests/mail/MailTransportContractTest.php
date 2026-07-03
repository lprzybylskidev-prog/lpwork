<?php

declare(strict_types=1);

use LPWork\Logging\LogChannel;
use LPWork\Mail\Transports\LogMailTransport;
use Tests\support\logging\InMemoryLogDriver;
use Tests\support\testing\Mail\MailTransportContract;

it('keeps the log mail transport compatible with the shared mail transport contract', function (): void {
    $transport = new LogMailTransport('log', new LogChannel('mail', new InMemoryLogDriver()), appDebug: true);

    new MailTransportContract($transport, 'log')->verifiesSendResultBehavior();
});
