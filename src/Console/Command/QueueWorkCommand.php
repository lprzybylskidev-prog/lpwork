<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Queue\QueueWorker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs a queue worker.
 */
class QueueWorkCommand extends Command
{
    /**
     * @var QueueWorker
     */
    private QueueWorker $worker;

    /**
     * @param QueueWorker $worker
     */
    public function __construct(QueueWorker $worker)
    {
        parent::__construct();
        $this->worker = $worker;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('lpwork:queue:work')
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
            ->addOption('tries', null, InputOption::VALUE_OPTIONAL, 'Maximum attempts per job', 1)
            ->addOption(
                'backoff',
                null,
                InputOption::VALUE_OPTIONAL,
                'Backoff seconds before retry',
                1,
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
        $tries = (int) $input->getOption('tries');
        $backoff = (int) $input->getOption('backoff');

        $this->worker->work(
            $queue ?? 'default',
            $sleep,
            $maxJobs,
            $maxTime,
            $tries,
            $backoff,
            $once,
        );

        return Command::SUCCESS;
    }
}
