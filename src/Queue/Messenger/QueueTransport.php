<?php
declare(strict_types=1);

namespace LPwork\Queue\Messenger;

use LPwork\Queue\Contract\QueueDriverInterface;
use LPwork\Queue\QueueJob;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Messenger transport bridging QueueDriverInterface.
 */
final class QueueTransport implements
    TransportInterface,
    ReceiverInterface,
    SenderInterface
{
    /**
     * @var QueueDriverInterface
     */
    private QueueDriverInterface $driver;

    /**
     * @param QueueDriverInterface $driver
     */
    public function __construct(QueueDriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @inheritDoc
     */
    public function send(Envelope $envelope): Envelope
    {
        $message = $envelope->getMessage();

        if (!$message instanceof QueueJob) {
            throw new MessageDecodingFailedException(
                "QueueTransport can only send QueueJob messages.",
            );
        }

        $delay = $envelope->last(DelayStamp::class);

        if ($delay instanceof DelayStamp) {
            $delayMs = $delay->getDelay();

            if ($delayMs > 0) {
                \usleep($delayMs * 1000);
            }
        }

        $this->driver->push($message);

        return $envelope;
    }

    /**
     * @inheritDoc
     */
    public function get(): iterable
    {
        $job = $this->driver->pop(1);

        if ($job === null) {
            return [];
        }

        return [new Envelope($job)];
    }

    /**
     * @inheritDoc
     */
    public function ack(Envelope $envelope): void
    {
        $message = $envelope->getMessage();

        if ($message instanceof QueueJob) {
            $this->driver->ack($message);
        }
    }

    /**
     * @inheritDoc
     */
    public function reject(Envelope $envelope): void
    {
        $message = $envelope->getMessage();

        if ($message instanceof QueueJob) {
            $this->driver->reject($message, false);
        }
    }
}
