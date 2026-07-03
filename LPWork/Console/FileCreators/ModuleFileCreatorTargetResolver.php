<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators;

use LPWork\Console\FileCreators\Exceptions\FileCreatorException;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\Modules\ModulePathResolver;
use LPWork\Foundation\Modules\ResolvedModule;

use function ltrim;
use function str_replace;
use function str_starts_with;
use function trim;

/**
 * Resolves module file creator target resolver values into runtime objects.
 */
final readonly class ModuleFileCreatorTargetResolver
{
    /**
     * Creates a new ModuleFileCreatorTargetResolver instance.
     */
    public function __construct(
        private ModulePathResolver $modules,
        private Filesystem $files,
        private Application $app,
    ) {}

    /**
     * Resolves configured input into a runtime value.
     */
    public function resolve(FileCreatorDefinition $definition, string $moduleName): ModuleFileCreatorTarget
    {
        $module = $this->modules->resolve($moduleName);

        if (!$this->files->isDirectory($module->path())) {
            throw FileCreatorException::moduleMissing($module->path());
        }

        $moduleRelativeDirectory = $this->moduleRelativeDirectory($definition);

        return new ModuleFileCreatorTarget(
            path: $this->relativePath($module->path($moduleRelativeDirectory)),
            namespace: $module->namespace(str_replace('/', '\\', $moduleRelativeDirectory)),
            registration: $this->moduleRegistration($module, $definition->registration()),
            optionalProvider: $this->optionalProvider($module, $definition->registration()),
        );
    }

    private function moduleRelativeDirectory(FileCreatorDefinition $definition): string
    {
        $directory = trim($definition->defaultDirectory(), '/');

        if ($directory === 'App/Shared/Configs') {
            return 'Configs';
        }

        if ($directory === 'App') {
            return '';
        }

        if (str_starts_with($directory, 'App/')) {
            return ltrim(str_replace('App/', '', $directory), '/');
        }

        throw FileCreatorException::moduleTargetNotSupported($definition->type());
    }

    private function moduleRegistration(ResolvedModule $module, ?ProviderRegistration $registration): ?ProviderRegistration
    {
        if ($registration === null) {
            return null;
        }

        $providerPath = trim($registration->providerPath(), '/');

        if ($providerPath === 'App/Shared/Configs/ConfigsProvider.php') {
            return $registration->forProviderPath($this->relativePath($module->path('Configs/ConfigsProvider.php')));
        }

        if ($providerPath === 'App/AppServiceProvider.php') {
            return $registration->forProviderPath($this->relativePath($module->serviceProviderPath()));
        }

        if (str_starts_with($providerPath, 'App/')) {
            return $registration->forProviderPath($this->relativePath($module->path(str_replace('App/', '', $providerPath))));
        }

        throw FileCreatorException::moduleTargetNotSupported($providerPath);
    }

    private function optionalProvider(ResolvedModule $module, ?ProviderRegistration $registration): ?OptionalModuleProvider
    {
        if ($registration === null) {
            return null;
        }

        $providerPath = trim($registration->providerPath(), '/');
        $moduleProvider = ProviderRegistration::list($this->relativePath($module->serviceProviderPath()), 'serviceProviders');

        return match ($providerPath) {
            'App/Console/ConsoleProvider.php' => new OptionalModuleProvider(
                path: $module->childProviderPath('Console', 'ConsoleProvider'),                /**
                 * Represents the namespace framework component.
                 */

                class: $module->namespace('Console') . '\\ConsoleProvider',
                contents: $this->consoleProvider($module),
                moduleRegistration: $moduleProvider,
            ),
            'App/Database/Migrations/MigrationsProvider.php' => new OptionalModuleProvider(
                path: $module->childProviderPath('Database/Migrations', 'MigrationsProvider'),                /**
                 * Represents the namespace framework component.
                 */

                class: $module->namespace('Database\Migrations') . '\\MigrationsProvider',
                contents: $this->migrationsProvider($module),
                moduleRegistration: $moduleProvider,
            ),
            'App/Database/Seeders/SeedersProvider.php' => new OptionalModuleProvider(
                path: $module->childProviderPath('Database/Seeders', 'SeedersProvider'),                /**
                 * Represents the namespace framework component.
                 */

                class: $module->namespace('Database\Seeders') . '\\SeedersProvider',
                contents: $this->seedersProvider($module),
                moduleRegistration: $moduleProvider,
            ),
            'App/Validation/ValidationProvider.php' => new OptionalModuleProvider(
                path: $module->childProviderPath('Validation', 'ValidationProvider'),                /**
                 * Represents the namespace framework component.
                 */

                class: $module->namespace('Validation') . '\\ValidationProvider',
                contents: $this->validationProvider($module),
                moduleRegistration: $moduleProvider,
            ),
            default => null,
        };
    }

    private function consoleProvider(ResolvedModule $module): string
    {
        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$module->namespace('Console')};

            use LPWork\Console\Contracts\Command;
            use LPWork\Console\Providers\CommandsProvider;

            final class ConsoleProvider extends CommandsProvider
            {
                /**
                 * @return list<class-string<Command>>
                 */
                protected function commands(): array
                {
                    return [];
                }
            }
            PHP;
    }

    private function migrationsProvider(ResolvedModule $module): string
    {
        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$module->namespace('Database\Migrations')};

            use LPWork\Database\Migrations\Contracts\Migration;
            use LPWork\Database\Migrations\Providers\MigrationsProvider as BaseMigrationsProvider;

            final class MigrationsProvider extends BaseMigrationsProvider
            {
                /**
                 * @return array<string, list<class-string<Migration>>>
                 */
                protected function migrations(): array
                {
                    return [];
                }
            }
            PHP;
    }

    private function seedersProvider(ResolvedModule $module): string
    {
        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$module->namespace('Database\Seeders')};

            use LPWork\Database\Seeders\Contracts\Seeder;
            use LPWork\Database\Seeders\Providers\SeedersProvider as BaseSeedersProvider;

            final class SeedersProvider extends BaseSeedersProvider
            {
                /**
                 * @return array<string, list<class-string<Seeder>>>
                 */
                protected function seeders(): array
                {
                    return [];
                }
            }
            PHP;
    }

    private function validationProvider(ResolvedModule $module): string
    {
        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$module->namespace('Validation')};

            use LPWork\Validation\Contracts\ValidationRule;
            use LPWork\Validation\Providers\ValidationRulesProvider;

            final class ValidationProvider extends ValidationRulesProvider
            {
                /**
                 * @return list<class-string<ValidationRule>>
                 */
                protected function validationRules(): array
                {
                    return [];
                }
            }
            PHP;
    }

    private function relativePath(string $path): string
    {
        return ltrim(str_replace($this->app->basePath(), '', $path), '/');
    }
}
