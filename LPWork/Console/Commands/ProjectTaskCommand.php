<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Console\ConsoleArgument;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Console\ProjectTasks\ProjectTask;
use LPWork\Console\ProjectTasks\ProjectTaskFilter;
use LPWork\Console\ProjectTasks\ProjectTaskRunner;
use LPWork\Console\ProjectTasks\ProjectTaskScope;

/**
 * Handles the project task command console command.
 */
final readonly class ProjectTaskCommand implements Command, DescribesInput
{
    /**
     * Creates a new ProjectTaskCommand instance.
     */
    public function __construct(
        private string $name,
        private string $description,
        private ProjectTask $task,
        private ProjectTaskRunner $runner,
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $module = $input->option('module');

        return $this->runner->run(
            $this->task,
            $output,
            ProjectTaskFilter::fromInput(
                ProjectTaskScope::fromFlags($input->hasOption('backend'), $input->hasOption('frontend')),
                is_string($module) && $module !== '' ? $module : null,
                $input->hasOption('browser'),
            ),
        );
    }

    /**
     * @return list<ConsoleArgument>
     */
    public function arguments(): array
    {
        return [];
    }

    /**
     * @return list<ConsoleOption>
     */
    public function options(): array
    {
        if ($this->task === ProjectTask::TestLpwork) {
            return [
                ConsoleOption::flag('browser', description: 'Also run framework Playwright browser tests.'),
            ];
        }

        if (!$this->supportsScopeOptions()) {
            return [];
        }

        $options = [
            ConsoleOption::flag('backend', description: 'Run only backend work for this project task.'),
            ConsoleOption::flag('frontend', description: 'Run only frontend work for this project task.'),
        ];

        if ($this->task === ProjectTask::Test) {
            $options[] = ConsoleOption::value('module', description: 'Run application tests only for the named module.');
        }

        return $options;
    }

    private function supportsScopeOptions(): bool
    {
        return match ($this->task) {
            ProjectTask::Format, ProjectTask::Check, ProjectTask::Test => true,
            ProjectTask::Coverage, ProjectTask::TestLpwork => false,
        };
    }
}
