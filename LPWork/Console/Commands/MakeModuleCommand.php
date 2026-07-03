<?php

declare(strict_types=1);

namespace LPWork\Console\Commands;

use LPWork\Console\ConsoleArgument;
use LPWork\Console\ConsoleMessageFormatter;
use LPWork\Console\ConsoleOption;
use LPWork\Console\Contracts\Command;
use LPWork\Console\Contracts\DescribesInput;
use LPWork\Console\FileCreators\Exceptions\FileCreatorException;
use LPWork\Console\Input;
use LPWork\Console\Modules\ModuleCreator;
use LPWork\Console\Output;
use LPWork\Foundation\Modules\Exceptions\InvalidModuleNameException;

/**
 * Handles the make module command console command.
 */
final readonly class MakeModuleCommand implements Command, DescribesInput
{
    /**
     * Creates a new MakeModuleCommand instance.
     */
    public function __construct(
        private ModuleCreator $modules,
        private ConsoleMessageFormatter $messages = new ConsoleMessageFormatter(),
    ) {}

    /**
     * Returns the configured name for this object.
     */
    public function name(): string
    {
        return 'make:module';
    }

    /**
     * Returns the user-facing description for this object.
     */
    public function description(): string
    {
        return 'Create an application module skeleton.';
    }

    /**
     * Handles the incoming operation and returns the expected result.
     */
    public function handle(Input $input, Output $output): int
    {
        $name = $input->argument(0);

        if ($name === null || $name === '') {
            $this->messages->error($output, 'Missing module name.');

            return 1;
        }

        try {
            $result = $this->modules->create(
                $name,
                register: $input->option('register') !== false,
                frontend: $input->option('frontend') !== false,
            );
        } catch (FileCreatorException|InvalidModuleNameException $exception) {
            $this->messages->error($output, $exception->getMessage());

            return 1;
        }

        $summary = [
            'Path' => $result->modulePath(),
            'Provider' => $result->serviceProviderClass(),
        ];

        if ($result->registered() && $result->registeredProviderPath() !== null) {
            $summary['Registered in'] = $result->registeredProviderPath();
        }

        $this->messages->success($output, 'Module created.');
        $this->messages->summary($output, $summary);

        return 0;
    }

    /**
     * @return list<ConsoleArgument>
     */
    public function arguments(): array
    {
        return [
            ConsoleArgument::required('name', 'Module name or nested module path.'),
        ];
    }

    /**
     * @return list<ConsoleOption>
     */
    public function options(): array
    {
        return [
            ConsoleOption::flag('no-register', description: 'Create the module without adding it to AppServiceProvider.'),
            ConsoleOption::flag('no-frontend', description: 'Create the module without frontend entrypoint files or asset registration.'),
        ];
    }
}
