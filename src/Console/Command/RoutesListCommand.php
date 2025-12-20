<?php
declare(strict_types=1);

namespace LPwork\Console\Command;

use LPwork\Http\Routing\RouteLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists all registered HTTP routes.
 */
class RoutesListCommand extends Command
{
    /**
     * @var RouteLoader
     */
    private RouteLoader $routeLoader;

    /**
     * @param RouteLoader $routeLoader
     */
    public function __construct(RouteLoader $routeLoader)
    {
        parent::__construct();
        $this->routeLoader = $routeLoader;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName("lpwork:routes:list")
            ->setAliases(["routes:list", "lpwork:routes"])
            ->setDescription("List registered HTTP routes");
    }

    /**
     * @inheritDoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $routes = $this->routeLoader->load()->all();

        $table = new Table($output);
        $table->setHeaders(["Method(s)", "Path", "Name"]);

        foreach ($routes as $route) {
            $table->addRow([
                \implode("|", $route->methods()),
                $route->path(),
                $route->name() ?? "",
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
