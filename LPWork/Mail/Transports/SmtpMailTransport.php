<?php

declare(strict_types=1);

namespace LPWork\Mail\Transports;

use function base64_encode;
use function bin2hex;
use function explode;
use function fclose;
use function fgets;
use function fwrite;
use function in_array;

use LPWork\Mail\Contracts\MailTransport;
use LPWork\Mail\Exceptions\MailTransportException;
use LPWork\Mail\MailAddress;
use LPWork\Mail\MailMessage;
use LPWork\Mail\MailMessageRenderer;
use LPWork\Mail\MailSendResult;

use function random_bytes;
use function rtrim;

use const STREAM_CLIENT_CONNECT;
use const STREAM_CRYPTO_METHOD_TLS_CLIENT;

use function stream_socket_client;
use function stream_socket_enable_crypto;

use Throwable;

/**
 * Represents the smtp mail transport framework component.
 */
final readonly class SmtpMailTransport implements MailTransport
{
    /**
     * Creates a new SmtpMailTransport instance.
     */
    public function __construct(
        private string $name,
        private string $host,
        private int $port,
        private ?string $username = null,
        private ?string $password = null,
        private ?string $encryption = null,
        private int $timeoutSeconds = 30,
        private MailMessageRenderer $renderer = new MailMessageRenderer(),
    ) {}

    /**
     * Runs send.
     */
    public function send(MailMessage $message): MailSendResult
    {
        $messageId = bin2hex(random_bytes(16)) . '@lpwork.local';
        $stream = $this->connect();

        try {
            $this->expect($stream, [220]);
            $this->command($stream, 'EHLO localhost', [250]);

            if ($this->encryption === 'tls') {
                $this->command($stream, 'STARTTLS', [220]);
                $enabled = @stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

                if ($enabled !== true) {
                    throw MailTransportException::connectionFailed($this->name);
                }

                $this->command($stream, 'EHLO localhost', [250]);
            }

            if ($this->username !== null && $this->username !== '') {
                $this->command($stream, 'AUTH LOGIN', [334]);
                $this->command($stream, base64_encode($this->username), [334]);
                $this->command($stream, base64_encode($this->password ?? ''), [235]);
            }

            $from = $message->fromAddress();
            $this->command($stream, 'MAIL FROM:' . ($from?->mailbox() ?? '<>'), [250]);

            foreach ($message->toAddresses() as $recipient) {
                $this->recipient($stream, $recipient);
            }

            $this->command($stream, 'DATA', [354]);
            $this->write($stream, $this->renderer->render($message, $messageId) . "\r\n.\r\n");
            $this->expect($stream, [250]);
            $this->command($stream, 'QUIT', [221]);
        } catch (Throwable $throwable) {
            throw $throwable instanceof MailTransportException
                ? $throwable
                : MailTransportException::sendFailed($this->name, $throwable);
        } finally {
            fclose($stream);
        }

        return new MailSendResult($this->name, $messageId);
    }

    /**
     * @return resource
     */
    private function connect(): mixed
    {
        $scheme = $this->encryption === 'ssl' ? 'ssl' : 'tcp';
        $stream = @stream_socket_client(
            $scheme . '://' . $this->host . ':' . $this->port,
            $errno,
            $error,
            $this->timeoutSeconds,
            STREAM_CLIENT_CONNECT,
        );

        if ($stream === false) {
            throw MailTransportException::connectionFailed($this->name);
        }

        return $stream;
    }

    /**
     * @param resource $stream
     * @param list<int> $codes
     */
    private function command(mixed $stream, string $command, array $codes): string
    {
        $this->write($stream, $command . "\r\n");

        return $this->expect($stream, $codes);
    }

    /**
     * @param resource $stream
     */
    private function recipient(mixed $stream, MailAddress $recipient): void
    {
        $this->command($stream, 'RCPT TO:' . $recipient->mailbox(), [250, 251]);
    }

    /**
     * @param resource $stream
     */
    private function write(mixed $stream, string $contents): void
    {
        if (@fwrite($stream, $contents) === false) {
            throw MailTransportException::writeFailed($this->name);
        }
    }

    /**
     * @param resource $stream
     * @param list<int> $codes
     */
    private function expect(mixed $stream, array $codes): string
    {
        $response = '';

        do {
            $line = fgets($stream);

            if ($line === false) {
                throw MailTransportException::unexpectedResponse($this->name, $response);
            }

            $response .= $line;
            $parts = explode(' ', $line, 2);
            $code = (int) $parts[0];
            $continues = isset($line[3]) && $line[3] === '-';
        } while ($continues);

        if (!in_array($code, $codes, true)) {
            throw MailTransportException::unexpectedResponse($this->name, rtrim($response));
        }

        return rtrim($response);
    }
}
