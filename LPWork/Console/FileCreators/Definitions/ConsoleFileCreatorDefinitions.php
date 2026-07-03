<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators\Definitions;

use LPWork\Console\FileCreators\FileCreatorDefinition;
use LPWork\Console\FileCreators\ProviderRegistration;

/**
 * Represents the console file creator definitions framework component.
 */
final readonly class ConsoleFileCreatorDefinitions implements FileCreatorDefinitionGroup
{
    /**
     * Returns all registered values for this component.
     */
    public function all(): array
    {
        return [
            new FileCreatorDefinition(
                type: 'command',
                description: 'Create an application console command.',
                defaultDirectory: 'App/Console/Commands',
                suffix: 'Command',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\Console\Contracts\Command;
                    use LPWork\Console\Input;
                    use LPWork\Console\Output;

                    final readonly class {{ class }} implements Command
                    {
                        public function name(): string
                        {
                            return '{{ command_name }}';
                        }

                        public function description(): string
                        {
                            return '{{ description }}';
                        }

                        public function handle(Input $input, Output $output): int
                        {
                            $output->writeln('{{ class }} executed.');

                            return 0;
                        }
                    }
                    PHP,
                registration: ProviderRegistration::list('App/Console/ConsoleProvider.php', 'commands'),
                replacements: ['description' => 'Application command.'],
            ),
            new FileCreatorDefinition(
                type: 'console-middleware',
                description: 'Create console middleware.',
                defaultDirectory: 'App/Console/Middleware',
                suffix: 'Middleware',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use Closure;
                    use LPWork\Console\Contracts\ConsoleMiddleware;
                    use LPWork\Console\Input;
                    use LPWork\Responses\ConsoleResponse;

                    final readonly class {{ class }} implements ConsoleMiddleware
                    {
                        /**
                         * @param Closure(Input): ConsoleResponse $next
                         */
                        public function handle(Input $input, Closure $next): ConsoleResponse
                        {
                            return $next($input);
                        }
                    }
                    PHP,
            ),
        ];
    }
}
