<?php

declare(strict_types=1);

namespace LPWork\Console\Modules;

use LPWork\Console\FileCreators\Exceptions\FileCreatorException;
use LPWork\Console\FileCreators\ProviderFileRegistrar;
use LPWork\Console\FileCreators\ProviderRegistration;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\Modules\ModulePathResolver;
use LPWork\Foundation\Modules\ResolvedModule;

use function strtolower;

/**
 * Represents the module creator framework component.
 */
final readonly class ModuleCreator
{
    /**
     * Creates a new ModuleCreator instance.
     */
    public function __construct(
        private ModulePathResolver $modules,
        private Filesystem $files,
        private ProviderFileRegistrar $registrar,
        private Application $app,
    ) {}

    /**
     * Creates a new value for this component.
     */
    public function create(string $name, bool $register = true, bool $frontend = true): ModuleCreatorResult
    {
        $module = $this->modules->resolve($name);

        if ($this->files->exists($module->path())) {
            throw FileCreatorException::alreadyExists($module->path());
        }

        $moduleKey = strtolower($module->name());
        $paths = $this->writeSkeleton($module, $moduleKey, $frontend);
        $registeredProviderPath = null;

        if ($register) {
            $registeredProviderPath = $this->registrar->register(
                ProviderRegistration::list('App/AppServiceProvider.php', 'serviceProviders'),
                $module->serviceProviderClass(),
                null,
            );
        }

        return new ModuleCreatorResult(
            modulePath: $module->path(),
            serviceProviderClass: $module->serviceProviderClass(),
            paths: $paths,
            registeredProviderPath: $registeredProviderPath,
        );
    }

    /**
     * @return list<string>
     */
    private function writeSkeleton(ResolvedModule $module, string $moduleKey, bool $frontend): array
    {
        $paths = [];

        foreach ($this->files($module, $moduleKey, $frontend) as $path => $contents) {
            if ($this->files->exists($path)) {
                throw FileCreatorException::alreadyExists($path);
            }

            $this->files->write($path, $contents);
            $paths[] = $path;
        }

        return $paths;
    }

    /**
     * @return array<string, string>
     */
    private function files(ResolvedModule $module, string $moduleKey, bool $frontend): array
    {
        $files = [
            $module->serviceProviderPath() => $this->serviceProvider($module, $frontend),
            $module->childProviderPath('Configs', 'ConfigsProvider') => $this->configProvider($module),
            $module->path('Configs/' . $module->name() . 'Config.php') => $this->configDefinition($module, $moduleKey),
            $module->childProviderPath('Routes', 'RoutesProvider') => $this->routesProvider($module),
            $module->path('Routes/WebRoutes.php') => $this->webRoutes($module),
            $module->childProviderPath('Translation', 'TranslationProvider') => $this->translationProvider($module, $moduleKey),
            $module->childProviderPath('View', 'ViewProvider') => $this->viewProvider($module, $moduleKey),
            $module->path('lang/en_US.json') => "{}\n",
            $module->path('resources/views/index.php') => $this->starterView($module),
            $module->path('tests/backend/' . $module->name() . 'ModuleTest.php') => $this->backendTest($module),
        ];

        if ($frontend) {
            $files = [
                ...array_slice($files, 0, 1, preserve_keys: true),
                $module->childProviderPath('Assets', 'AssetsProvider') => $this->assetsProvider($module, $moduleKey),
                ...array_slice($files, 1, null, preserve_keys: true),
                $module->path('resources/frontend/app.ts') => $this->frontendEntry(),
                $module->path('resources/frontend/app.css') => $this->frontendStyles(),
                $module->path('tests/frontend/app.test.ts') => $this->frontendTest($moduleKey),
            ];
        }

        return $files;
    }

    private function serviceProvider(ResolvedModule $module, bool $frontend): string
    {
        $providers = [
            'Configs' => 'ConfigsProvider',
            'Routes' => 'RoutesProvider',
            'Translation' => 'TranslationProvider',
            'View' => 'ViewProvider',
        ];

        if ($frontend) {
            $providers = ['Assets' => 'AssetsProvider', ...$providers];
        }

        return $this->serviceProviderWithChildren($module, $providers);
    }

    /**
     * @param array<string, string> $providers
     */
    private function serviceProviderWithChildren(ResolvedModule $module, array $providers): string
    {
        $namespace = $module->namespace();
        $class = $module->name() . 'ServiceProvider';
        $uses = '';
        $entries = '';

        foreach ($providers as $namespaceSuffix => $providerClass) {
            $uses .= "use {$module->namespace($namespaceSuffix)}\\{$providerClass};\n";
            $entries .= "            {$providerClass}::class,\n";
        }

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$namespace};

            {$uses}use LPWork\Foundation\Contracts\ServiceProvider;
            use LPWork\Foundation\Providers\ProviderServiceProvider;

            final class {$class} extends ProviderServiceProvider
            {
                /**
                 * @return list<class-string<ServiceProvider>>
                 */
                protected function serviceProviders(): array
                {
                    return [
            {$entries}        ];
                }
            }
            PHP;
    }

    private function assetsProvider(ResolvedModule $module, string $moduleKey): string
    {
        $entryPath = $this->relativeModulePath($module, 'resources/frontend/app.ts');

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$module->namespace('Assets')};

            use LPWork\Frontend\Providers\AssetEntrypointsProvider;

            final class AssetsProvider extends AssetEntrypointsProvider
            {
                /**
                 * @return array<string, string>
                 */
                protected function assetEntries(): array
                {
                    return [
                        '{$moduleKey}::app' => '{$entryPath}',
                    ];
                }
            }
            PHP;
    }

    private function configProvider(ResolvedModule $module): string
    {
        $configClass = $module->name() . 'Config';

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$module->namespace('Configs')};

            use LPWork\Config\Contracts\ConfigDefinition;
            use LPWork\Config\Providers\ConfigDefinitionsProvider;

            final class ConfigsProvider extends ConfigDefinitionsProvider
            {
                /**
                 * @return list<class-string<ConfigDefinition>>
                 */
                public function configDefinitions(): array
                {
                    return [
                        {$configClass}::class,
                    ];
                }
            }
            PHP;
    }

    private function configDefinition(ResolvedModule $module, string $moduleKey): string
    {
        $class = $module->name() . 'Config';

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$module->namespace('Configs')};

            use LPWork\Config\Contracts\ConfigDefinition;

            final class {$class} implements ConfigDefinition
            {
                public function key(): string
                {
                    return '{$moduleKey}';
                }

                /**
                 * @return array<array-key, mixed>
                 */
                public function values(): array
                {
                    return [];
                }
            }
            PHP;
    }


    private function routesProvider(ResolvedModule $module): string
    {
        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$module->namespace('Routes')};

            use LPWork\Routing\Contracts\RouteDefinition;
            use LPWork\Routing\Providers\RoutesProvider as BaseRoutesProvider;

            final class RoutesProvider extends BaseRoutesProvider
            {
                /**
                 * @return list<class-string<RouteDefinition>>
                 */
                protected function routeDefinitions(): array
                {
                    return [
                        WebRoutes::class,
                    ];
                }
            }
            PHP;
    }

    private function webRoutes(ResolvedModule $module): string
    {
        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$module->namespace('Routes')};

            use LPWork\Routing\Contracts\RouteDefinition;
            use LPWork\Routing\Router;

            final class WebRoutes implements RouteDefinition
            {
                public function register(Router \$router): void
                {
                    //
                }
            }
            PHP;
    }

    private function translationProvider(ResolvedModule $module, string $moduleKey): string
    {
        $translationPath = $this->relativeModulePath($module, 'lang');

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$module->namespace('Translation')};

            use LPWork\Container\Container;
            use LPWork\Container\Exceptions\CannotResolveDependencyException;
            use LPWork\Foundation\Application;
            use LPWork\Foundation\ServiceProvider;
            use LPWork\Translation\TranslationNamespaceRegistry;

            final class TranslationProvider extends ServiceProvider
            {
                public function register(Container \$container): void
                {
                    \$app = \$container->make(Application::class);
                    \$translations = \$container->make(TranslationNamespaceRegistry::class);

                    if (!\$app instanceof Application) {
                        throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
                    }

                    if (!\$translations instanceof TranslationNamespaceRegistry) {
                        throw CannotResolveDependencyException::factoryDidNotReturnObject(TranslationNamespaceRegistry::class);
                    }

                    \$translations->add('{$moduleKey}', \$app->basePath('{$translationPath}'));
                }
            }
            PHP;
    }

    private function viewProvider(ResolvedModule $module, string $moduleKey): string
    {
        $viewPath = $this->relativeModulePath($module, 'resources/views');

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$module->namespace('View')};

            use LPWork\View\Providers\PhpViewEngineProvider;

            final class ViewProvider extends PhpViewEngineProvider
            {
                /**
                 * @return array<string, string>
                 */
                protected function viewNamespaces(): array
                {
                    return [
                        '{$moduleKey}' => '{$viewPath}',
                    ];
                }
            }
            PHP;
    }

    private function starterView(ResolvedModule $module): string
    {
        return <<<PHP
            <?php

            declare(strict_types=1);

            use LPWork\View\Contracts\ViewContext;


            ?>
            <h1>{$module->name()}</h1>
            PHP;
    }

    private function frontendEntry(): string
    {
        return <<<TS
            import './app.css';

            TS;
    }

    private function frontendStyles(): string
    {
        return "body {}\n";
    }

    private function backendTest(ResolvedModule $module): string
    {
        return <<<PHP
            <?php

            declare(strict_types=1);

            use {$module->serviceProviderClass()};
            use LPWork\Foundation\Contracts\ServiceProvider;

            it('exposes the {$module->name()} module service provider', function (): void {
                expect({$module->name()}ServiceProvider::class)->toImplement(ServiceProvider::class);
            });
            PHP;
    }

    private function frontendTest(string $moduleKey): string
    {
        return <<<TS
            import { describe, expect, it } from 'vitest';

            describe('{$moduleKey} frontend assets', () => {
                it('keeps the module frontend test harness active', () => {
                    expect('{$moduleKey}').toBe('{$moduleKey}');
                });
            });
            TS;
    }

    private function relativeModulePath(ResolvedModule $module, string $path): string
    {
        return ltrim(str_replace($this->app->basePath(), '', $module->path($path)), '/');
    }
}
