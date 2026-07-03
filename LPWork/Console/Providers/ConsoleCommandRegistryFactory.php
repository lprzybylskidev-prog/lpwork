<?php

declare(strict_types=1);

namespace LPWork\Console\Providers;

use LPWork\Console\CommandRegistry;
use LPWork\Console\Commands\AboutCommand;
use LPWork\Console\Commands\BrowserTaskCommand;
use LPWork\Console\Commands\CacheClearCommand;
use LPWork\Console\Commands\CacheRebuildCommand;
use LPWork\Console\Commands\CompletionGenerateCommand;
use LPWork\Console\Commands\CompletionInstallCommand;
use LPWork\Console\Commands\ConfigCacheCommand;
use LPWork\Console\Commands\ConfigClearCommand;
use LPWork\Console\Commands\ConfigShowCommand;
use LPWork\Console\Commands\ConfigValidateCommand;
use LPWork\Console\Commands\FileCreatorCommand;
use LPWork\Console\Commands\FrontendEntrypointsCommand;
use LPWork\Console\Commands\FrontendTaskCommand;
use LPWork\Console\Commands\GenerateApplicationKeyCommand;
use LPWork\Console\Commands\MakeModuleCommand;
use LPWork\Console\Commands\ProjectTaskCommand;
use LPWork\Console\Commands\RouteListCommand;
use LPWork\Console\Completion\CompletionInstaller;
use LPWork\Console\Completion\CompletionScriptGenerator;
use LPWork\Console\FileCreators\FileCreator;
use LPWork\Console\FileCreators\FileCreatorDefinitions;
use LPWork\Console\ProjectTasks\ProjectTask;
use LPWork\Console\ProjectTasks\ProjectTaskRunner;
use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Foundation\CompiledCacheRegistry;
use LPWork\Frontend\BrowserTask;
use LPWork\Frontend\BrowserTaskRunner;
use LPWork\Frontend\FrontendTask;
use LPWork\Frontend\FrontendTaskRunner;
use LPWork\Health\Commands\HealthCheckCommand;

/**
 * Creates console command registry factory instances from framework configuration.
 */
final readonly class ConsoleCommandRegistryFactory
{
    /**
     * Creates a new value for this component.
     */
    public function create(Container $container): CommandRegistry
    {
        $registry = new CommandRegistry();
        $aboutCommand = $this->make($container, AboutCommand::class);
        $browserTasks = $this->make($container, BrowserTaskRunner::class);
        $cacheClearCommand = $this->make($container, CacheClearCommand::class);
        $compiledCaches = $this->make($container, CompiledCacheRegistry::class);
        $configCacheCommand = $this->make($container, ConfigCacheCommand::class);
        $configClearCommand = $this->make($container, ConfigClearCommand::class);
        $configShowCommand = $this->make($container, ConfigShowCommand::class);
        $configValidateCommand = $this->make($container, ConfigValidateCommand::class);
        $healthCheckCommand = $this->make($container, HealthCheckCommand::class);
        $keyCommand = $this->make($container, GenerateApplicationKeyCommand::class);
        $routeListCommand = $this->make($container, RouteListCommand::class);
        $completionGenerator = $this->make($container, CompletionScriptGenerator::class);
        $completionInstaller = $this->make($container, CompletionInstaller::class);
        $fileCreatorDefinitions = $this->make($container, FileCreatorDefinitions::class);
        $fileCreator = $this->make($container, FileCreator::class);
        $frontendTasks = $this->make($container, FrontendTaskRunner::class);
        $frontendEntries = $this->make($container, FrontendEntrypointsCommand::class);
        $makeModuleCommand = $this->make($container, MakeModuleCommand::class);
        $projectTasks = $this->make($container, ProjectTaskRunner::class);

        $registry->add($aboutCommand);
        $registry->add($cacheClearCommand);
        $registry->add(new CacheRebuildCommand($compiledCaches));
        $registry->add(new CompletionGenerateCommand($registry, $completionGenerator));
        $registry->add(new CompletionInstallCommand($completionInstaller));
        $registry->add($configCacheCommand);
        $registry->add($configClearCommand);
        $registry->add($configShowCommand);
        $registry->add($configValidateCommand);
        $registry->add($healthCheckCommand);
        $registry->add($keyCommand);
        $registry->add($makeModuleCommand);
        $registry->add($routeListCommand);
        $registry->add($frontendEntries);

        foreach ($fileCreatorDefinitions->all() as $definition) {
            $registry->add(new FileCreatorCommand($definition, $fileCreator));
        }

        foreach ($this->browserTaskCommands() as $definition) {
            $registry->add(new BrowserTaskCommand(
                $definition['name'],
                $definition['description'],
                $definition['task'],
                $browserTasks,
            ));
        }

        foreach ($this->frontendTaskCommands() as $definition) {
            $registry->add(new FrontendTaskCommand(
                $definition['name'],
                $definition['description'],
                $definition['task'],
                $frontendTasks,
            ));
        }

        foreach ($this->projectTaskCommands() as $definition) {
            $registry->add(new ProjectTaskCommand(
                $definition['name'],
                $definition['description'],
                $definition['task'],
                $projectTasks,
            ));
        }

        return $registry;
    }

    /**
     * @return list<array{name: string, description: string, task: BrowserTask}>
     */
    private function browserTaskCommands(): array
    {
        return [
            [
                'name' => 'browser:install',
                'description' => 'Install Playwright browsers.',
                'task' => BrowserTask::Install,
            ],
            [
                'name' => 'browser:test',
                'description' => 'Run Playwright browser tests.',
                'task' => BrowserTask::Test,
            ],
            [
                'name' => 'browser:ui',
                'description' => 'Open the Playwright test UI.',
                'task' => BrowserTask::Ui,
            ],
        ];
    }

    /**
     * @return list<array{name: string, description: string, task: FrontendTask}>
     */
    private function frontendTaskCommands(): array
    {
        return [
            [
                'name' => 'frontend:install',
                'description' => 'Install frontend dependencies with the detected package manager.',
                'task' => FrontendTask::Install,
            ],
            [
                'name' => 'frontend:dev',
                'description' => 'Run the Vite development server.',
                'task' => FrontendTask::Dev,
            ],
            [
                'name' => 'frontend:build',
                'description' => 'Build frontend assets with Vite.',
                'task' => FrontendTask::Build,
            ],
            [
                'name' => 'frontend:clean',
                'description' => 'Clean generated frontend artifacts.',
                'task' => FrontendTask::Clean,
            ],
            [
                'name' => 'frontend:format',
                'description' => 'Format frontend files.',
                'task' => FrontendTask::Format,
            ],
            [
                'name' => 'frontend:check',
                'description' => 'Run frontend static checks.',
                'task' => FrontendTask::Check,
            ],
            [
                'name' => 'frontend:test',
                'description' => 'Run frontend unit tests.',
                'task' => FrontendTask::Test,
            ],
        ];
    }

    /**
     * @return list<array{name: string, description: string, task: ProjectTask}>
     */
    private function projectTaskCommands(): array
    {
        return [
            [
                'name' => 'format',
                'description' => 'Format project PHP files with PHP CS Fixer.',
                'task' => ProjectTask::Format,
            ],
            [
                'name' => 'check',
                'description' => 'Run static analysis for project PHP files.',
                'task' => ProjectTask::Check,
            ],
            [
                'name' => 'test',
                'description' => 'Run application module tests.',
                'task' => ProjectTask::Test,
            ],
            [
                'name' => 'coverage',
                'description' => 'Run the Pest test suite with coverage.',
                'task' => ProjectTask::Coverage,
            ],
            [
                'name' => 'test:lpwork',
                'description' => 'Run the LPWork framework Pest test suite.',
                'task' => ProjectTask::TestLpwork,
            ],
        ];
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function make(Container $container, string $class): object
    {
        $instance = $container->make($class);

        if (!$instance instanceof $class) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject($class);
        }

        return $instance;
    }
}
