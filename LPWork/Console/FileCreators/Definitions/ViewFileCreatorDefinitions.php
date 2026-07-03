<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators\Definitions;

use LPWork\Console\FileCreators\FileCreatorDefinition;
use LPWork\Console\FileCreators\ProviderRegistration;

/**
 * Represents the view file creator definitions framework component.
 */
final readonly class ViewFileCreatorDefinitions implements FileCreatorDefinitionGroup
{
    /**
     * Returns all registered values for this component.
     */
    public function all(): array
    {
        return [
            new FileCreatorDefinition(
                type: 'view-engine',
                description: 'Create a view engine.',
                defaultDirectory: 'App/View/Engines',
                suffix: 'ViewEngine',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use LPWork\View\Contracts\ViewEngine;
                    use LPWork\View\ViewRenderContext;

                    use function file_get_contents;
                    use function is_string;

                    final readonly class {{ class }} implements ViewEngine
                    {
                        /**
                         * @param array<string, mixed>|object $data
                         */
                        public function render(string $path, array|object $data, ViewRenderContext $context): string
                        {
                            $contents = file_get_contents($path);

                            return is_string($contents) ? $contents : '';
                        }
                    }
                    PHP,
                registration: ProviderRegistration::list('App/View/ViewProvider.php', 'viewEngines'),
            ),
            new FileCreatorDefinition(
                type: 'view-extension',
                description: 'Create a PHP view extension provider.',
                defaultDirectory: 'App/View/Extensions',
                suffix: 'ViewExtension',
                template: <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    namespace {{ namespace }};

                    use Closure;
                    use LPWork\View\Providers\PhpViewEngineProvider;

                    final class {{ class }} extends PhpViewEngineProvider
                    {
                        /**
                         * @return array<string, mixed>
                         */
                        protected function globals(): array
                        {
                            return [];
                        }

                        /**
                         * @return array<string, Closure>
                         */
                        protected function functions(): array
                        {
                            return [];
                        }
                    }
                    PHP,
                registration: ProviderRegistration::list('App/AppServiceProvider.php', 'serviceProviders'),
            ),
        ];
    }
}
