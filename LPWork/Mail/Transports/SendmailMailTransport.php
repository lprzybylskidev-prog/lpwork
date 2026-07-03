<?php

declare(strict_types=1);

namespace LPWork\Mail\Transports;

use LPWork\Mail\Contracts\MailTransport;
use LPWork\Mail\Exceptions\MailTransportException;
use LPWork\Mail\MailMessage;
use LPWork\Mail\MailMessageRenderer;
use LPWork\Mail\MailSendResult;
use Throwable;

/**
 * Represents the sendmail mail transport framework component.
 */
final readonly class SendmailMailTransport implements MailTransport
{
    /**
     * Creates a new SendmailMailTransport instance.
     */
    public function __construct(
        private string $name,
        private string $command = '/usr/sbin/sendmail -t -i',
        private MailMessageRenderer $renderer = new MailMessageRenderer(),
    ) {}

    /**
     * Runs send.
     */
    public function send(MailMessage $message): MailSendResult
    {
        $messageId = bin2hex(random_bytes(16)) . '@lpwork.local';
        $process = @proc_open($this->command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($process) || !isset($pipes[0])) {
            throw MailTransportException::connectionFailed($this->name);
        }

        try {
            fwrite($pipes[0], $this->renderer->render($message, $messageId));
            fclose($pipes[0]);
            $exitCode = proc_close($process);
        } catch (Throwable $throwable) {
            throw MailTransportException::sendFailed($this->name, $throwable);
        }

        if ($exitCode !== 0) {
            throw MailTransportException::unexpectedResponse($this->name, 'sendmail exited with status ' . $exitCode);
        }

        return new MailSendResult($this->name, $messageId);
    }
}
