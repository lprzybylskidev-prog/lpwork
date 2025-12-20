<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Version\FrameworkVersion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Displays LPwork framework version.
 */
class VersionCommand extends Command
{
    /**
     * @var FrameworkVersion
     */
    private FrameworkVersion $version;

    /**
     * @param FrameworkVersion $version
     */
    public function __construct(FrameworkVersion $version)
    {
        parent::__construct();
        $this->version = $version;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName("lpwork:version")
            ->setAliases(["version"])
            ->setDescription("Show LPwork framework version");
    }

    /**
     * @inheritDoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $output->writeln($this->version->get());

        return Command::SUCCESS;
    }
}
