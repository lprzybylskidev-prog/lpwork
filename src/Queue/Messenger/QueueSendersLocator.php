<?php
declare(strict_types=1);

namespace LPwork\Queue\Messenger;

use LPwork\Queue\QueueJob;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Resolves senders (transports) based on QueueJob queue name.
 */
final class QueueSendersLocator implements SendersLocatorInterface
{
    /**
     * @var array<string, TransportInterface>
     */
    private array $transports;

    /**
     * @param array<string, TransportInterface> $transports
     */
    public function __construct(array $transports)
    {
        $this->transports = $transports;
    }

    /**
     * @inheritDoc
     */
    public function getSenders(Envelope $envelope): iterable
    {
        $message = $envelope->getMessage();

        if (!$message instanceof QueueJob) {
            return [];
        }

        $queue = $message->queue();

        if (!isset($this->transports[$queue])) {
            return [];
        }

        return [$queue => $this->transports[$queue]];
    }
}
