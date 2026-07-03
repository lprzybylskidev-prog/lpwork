<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Console\ConsoleArgument;
use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\FileCreators\Exceptions\FileCreatorException;
use LPWork\Console\FileCreators\FileCreator;
use LPWork\Console\FileCreators\FileCreatorDefinition;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Foundation\Modules\Exceptions\InvalidModuleNameException;

/**
 * Handles the file creator command console command.
 */
final readonly class FileCreatorCommand implements Command, DescribesInput
{
    /**
     * Creates a new FileCreatorCommand instance.
     */
    public function __construct(
        private FileCreatorDefinition $definition,
        private FileCreator $creator,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return $this->definition->commandName();
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return $this->definition->description();
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $name = $input->argument(0);

        if ($name === null || $name === '') {
            $this->messages->error($output, "Missing {$this->definition->type()} name.");

            return 1;
        }

        try {
            $result = $this->creator->create(
                definition: $this->definition,
                name: $name,
                path: $this->stringOption($input, 'path'),
                namespace: $this->stringOption($input, 'namespace'),
                register: $input->hasOption('register'),
                group: $this->stringOption($input, 'connection'),
                module: $this->stringOption($input, 'module'),
            );
        } catch (FileCreatorException|InvalidModuleNameException $exception) {
            $this->messages->error($output, $exception->getMessage());

            return 1;
        }

        $this->messages->success($output, sprintf('%s created.', $this->typeLabel()));
        $summary = [
            'Path' => $result->path(),
        ];
        $module = $this->stringOption($input, 'module');

        if ($module !== null && $module !== '') {
            $summary['Module'] = $module;
        }

        if ($result->registered() && $result->providerPath() !== null) {
            $summary['Registered in'] = $result->providerPath();
        }

        $this->messages->summary($output, $summary);

        return 0;
    }

    /**
     * @return list<ConsoleArgument>
     */
    public function arguments(): array
    {
        return [
            ConsoleArgument::required('name', 'Class name or nested class path.'),
        ];
    }

    /**
     * @return list<ConsoleOption>
     */
    public function options(): array
    {
        $options = [
            ConsoleOption::value('path', null, 'Directory where the file should be created. Defaults to the framework app layout.'),
            ConsoleOption::value('namespace', null, 'Namespace to use when the path is outside App or LPWork.'),
            ConsoleOption::value('module', null, 'Application module where the file should be created.'),
            ConsoleOption::flag('register', null, 'Register the generated class in its application provider when supported.'),
        ];

        $registration = $this->definition->registration();
        $groupOption = $registration?->groupOption();

        if ($groupOption !== null) {
            $options[] = ConsoleOption::value($groupOption, null, 'Provider group to register under.');
        }

        return $options;
    }

    private function stringOption(Input $input, string $name): ?string
    {
        $value = $input->option($name);

        return is_string($value) ? $value : null;
    }

    private function typeLabel(): string
    {
        return ucfirst(str_replace('-', ' ', $this->definition->type()));
    }
}
