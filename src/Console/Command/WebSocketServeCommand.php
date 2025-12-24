<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\WebSocket\WebSocketConfiguration;
use LPwork\WebSocket\WebSocketServerFactory;
use LPwork\WebSocket\Contract\WebSocketComponentRegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs the WebSocket server.
 */
class WebSocketServeCommand extends Command
{
    /**
     * @var WebSocketServerFactory
     */
    private WebSocketServerFactory $serverFactory;

    /**
     * @var WebSocketConfiguration
     */
    private WebSocketConfiguration $configuration;

    /**
     * @var WebSocketComponentRegistryInterface
     */
    private WebSocketComponentRegistryInterface $componentRegistry;

    /**
     * @param WebSocketServerFactory           $serverFactory
     * @param WebSocketConfiguration           $configuration
     * @param WebSocketComponentRegistryInterface $componentRegistry
     */
    public function __construct(
        WebSocketServerFactory $serverFactory,
        WebSocketConfiguration $configuration,
        WebSocketComponentRegistryInterface $componentRegistry,
    ) {
        parent::__construct();
        $this->serverFactory = $serverFactory;
        $this->configuration = $configuration;
        $this->componentRegistry = $componentRegistry;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('lpwork:websocket:serve')
            ->setDescription('Start the WebSocket server')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Bind host')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Bind port')
            ->addOption('server', null, InputOption::VALUE_OPTIONAL, 'Server name');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hostOption = $input->getOption('host');
        $portOption = $input->getOption('port');
        $serverOption = $input->getOption('server');

        $serverName =
            \is_string($serverOption) && $serverOption !== ''
                ? $serverOption
                : $this->configuration->defaultServer();

        $serverConfig = $this->configuration->server($serverName);

        if (!(bool) ($serverConfig['enabled'] ?? true)) {
            $output->writeln(
                \sprintf(
                    "<comment>Server \"%s\" is disabled in configuration.</comment>",
                    $serverName,
                ),
            );

            return Command::SUCCESS;
        }

        $host =
            \is_string($hostOption) && $hostOption !== ''
                ? $hostOption
                : (string) ($serverConfig['host'] ?? '0.0.0.0');
        $port = \is_numeric($portOption)
            ? (int) $portOption
            : (int) ($serverConfig['port'] ?? 8081);

        $output->writeln(
            \sprintf(
                "<info>Starting WebSocket server \"%s\" on %s:%d</info>",
                $serverName,
                $host,
                $port,
            ),
        );

        $components = $this->componentRegistry->getComponents();

        if (!isset($components[$serverName])) {
            $output->writeln(
                \sprintf("<error>No component registered for server \"%s\".</error>", $serverName),
            );

            return Command::FAILURE;
        }

        $serverConfig['host'] = $host;
        $serverConfig['port'] = $port;

        $server = $this->serverFactory->createServer($serverConfig, $components[$serverName]);
        $server->run();

        return Command::SUCCESS;
    }
}
