<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMessageLimitListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnTimeLimitListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMemoryLimitListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Runs a queue worker.
 */
class QueueWorkCommand extends Command
{
    /**
     * @var MessageBusInterface
     */
    private MessageBusInterface $bus;

    /**
     * @var array<string, ReceiverInterface>
     */
    private array $receivers;

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $senders;

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $retryStrategies;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param MessageBusInterface              $bus
     * @param array<string, ReceiverInterface> $receivers
     * @param ContainerInterface               $senders
     * @param ContainerInterface               $retryStrategies
     * @param LoggerInterface                  $logger
     */
    public function __construct(
        MessageBusInterface $bus,
        array $receivers,
        ContainerInterface $senders,
        ContainerInterface $retryStrategies,
        LoggerInterface $logger,
    ) {
        parent::__construct();
        $this->bus = $bus;
        $this->receivers = $receivers;
        $this->senders = $senders;
        $this->retryStrategies = $retryStrategies;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('lpwork:queue:work')
            ->setAliases(['queue:work'])
            ->setDescription('Process jobs from a queue')
            ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'Queue name', null)
            ->addOption('sleep', null, InputOption::VALUE_OPTIONAL, 'Sleep seconds when idle', 1)
            ->addOption('once', null, InputOption::VALUE_NONE, 'Process only one job and exit')
            ->addOption(
                'max-jobs',
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum jobs to process',
                null,
            )
            ->addOption(
                'max-time',
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum runtime in seconds',
                null,
            )
            ->addOption(
                'memory',
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum memory usage in MB',
                null,
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queue = $input->getOption('queue');
        $sleep = (int) $input->getOption('sleep');
        $once = (bool) $input->getOption('once');
        $maxJobs =
            $input->getOption('max-jobs') !== null ? (int) $input->getOption('max-jobs') : null;
        $maxTime =
            $input->getOption('max-time') !== null ? (int) $input->getOption('max-time') : null;
        $memoryLimit =
            $input->getOption('memory') !== null ? (int) $input->getOption('memory') : null;
        $receiverName = $queue ?? 'default';
        if (!isset($this->receivers[$receiverName])) {
            $output->writeln(
                \sprintf('<error>Queue receiver "%s" is not configured.</error>', $receiverName),
            );

            return Command::FAILURE;
        }

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(
            new SendFailedMessageForRetryListener(
                $this->senders,
                $this->retryStrategies,
                $this->logger,
                $dispatcher,
            ),
        );
        $worker = new Worker(
            [$receiverName => $this->receivers[$receiverName]],
            $this->bus,
            $dispatcher,
        );

        if ($once) {
            $dispatcher->addSubscriber(new StopWorkerOnMessageLimitListener(1));
        }

        if ($maxJobs !== null) {
            $dispatcher->addSubscriber(new StopWorkerOnMessageLimitListener($maxJobs));
        }

        if ($maxTime !== null) {
            $dispatcher->addSubscriber(new StopWorkerOnTimeLimitListener($maxTime));
        }

        if ($memoryLimit !== null) {
            $dispatcher->addSubscriber(
                new StopWorkerOnMemoryLimitListener($memoryLimit * 1024 * 1024),
            );
        }

        $worker->run(['sleep' => $sleep * 1_000_000]);

        return Command::SUCCESS;
    }
}
