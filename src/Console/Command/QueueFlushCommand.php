<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Queue\QueueManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Flushes a queue.
 */
class QueueFlushCommand extends Command
{
    /**
     * @var QueueManager
     */
    private QueueManager $queues;

    /**
     * @param QueueManager $queues
     */
    public function __construct(QueueManager $queues)
    {
        parent::__construct();
        $this->queues = $queues;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('lpwork:queue:flush')
            ->setDescription('Purge all pending jobs from a queue')
            ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'Queue name', null);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queue = $input->getOption('queue');
        $driver = $this->queues->queue($queue);
        $driver->purge();
        $output->writeln('<info>Queue flushed.</info>');

        return Command::SUCCESS;
    }
}
