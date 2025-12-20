<?php
declare(strict_types=1);

namespace LPwork\Kernel;

use Config\CommandProvider as AppCommandProvider;
use LPwork\Config\Contract\ConfigRepositoryInterface;
use LPwork\Console\Provider\BuiltinCommandProvider;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

/**
 * Handles the CLI runtime lifecycle.
 */
class CliKernel
{
    /**
     * @var BuiltinCommandProvider
     */
    private BuiltinCommandProvider $builtinCommandProvider;

    /**
     * @var AppCommandProvider
     */
    private AppCommandProvider $appCommandProvider;

    /**
     * @var ConfigRepositoryInterface
     */
    private ConfigRepositoryInterface $configRepository;

    /**
     * @param ConfigRepositoryInterface $configRepository
     * @param BuiltinCommandProvider    $builtinCommandProvider
     * @param AppCommandProvider        $appCommandProvider
     */
    public function __construct(
        ConfigRepositoryInterface $configRepository,
        BuiltinCommandProvider $builtinCommandProvider,
        AppCommandProvider $appCommandProvider,
    ) {
        $this->configRepository = $configRepository;
        $this->builtinCommandProvider = $builtinCommandProvider;
        $this->appCommandProvider = $appCommandProvider;
    }

    /**
     * Boots and runs the CLI kernel.
     *
     * @return void
     */
    public function run(): void
    {
        $application = new Application(
            $this->configRepository->getString('app.name', 'LPwork'),
            $this->configRepository->getString('app.version', '0.0.1'),
        );

        foreach ($this->collectCommands() as $command) {
            $application->addCommand($command);
        }

        $application->run();
    }

    /**
     * Collects commands from providers, allowing application commands to override built-ins.
     *
     * @return array<int, Command>
     */
    private function collectCommands(): array
    {
        $providers = [$this->builtinCommandProvider, $this->appCommandProvider];

        $commands = [];

        foreach ($providers as $provider) {
            foreach ($provider->getCommands() as $command) {
                $name = $command->getName();

                if ($name === null || $name === '') {
                    continue;
                }

                $commands[$name] = $command;
            }
        }

        return \array_values($commands);
    }
}
