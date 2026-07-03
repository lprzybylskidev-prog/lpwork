<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators;

use LPWork\Console\FileCreators\Exceptions\FileCreatorException;
use LPWork\Filesystem\Filesystem;

use function str_starts_with;

/**
 * Represents the file creator framework component.
 */
final readonly class FileCreator
{
    /**
     * Creates a new FileCreator instance.
     */
    public function __construct(
        private FileCreatorPathResolver $paths,
        private FileCreatorTemplateRenderer $templates,
        private ProviderFileRegistrar $registrar,
        private Filesystem $files,
        private ModuleFileCreatorTargetResolver $moduleTargets,
    ) {}

    /**
     * Creates a new value for this component.
     */
    public function create(
        FileCreatorDefinition $definition,
        string $name,
        ?string $path = null,
        ?string $namespace = null,
        bool $register = false,
        ?string $group = null,
        ?string $module = null,
    ): FileCreatorResult {
        $registration = $definition->registration();
        $target = null;

        if ($module !== null && $module !== '') {
            if (($path !== null && $path !== '') || ($namespace !== null && $namespace !== '')) {
                throw FileCreatorException::moduleCannotUseCustomPath();
            }

            $target = $this->moduleTargets->resolve($definition, $module);
            $path = $target->path();
            $namespace = $target->namespace();
            $registration = $target->registration();
        }

        if (($module === null || $module === '') && ($path === null || $path === '') && $this->requiresModuleOrPath($definition)) {
            throw FileCreatorException::moduleOrPathRequired($definition->type());
        }

        $file = $this->paths->resolve($definition, $name, $path, $namespace);

        if ($this->files->exists($file->path())) {
            throw FileCreatorException::alreadyExists($file->path());
        }

        $this->files->write($file->path(), $this->templates->render($definition, $file));

        if (!$register) {
            return new FileCreatorResult($file->path(), $file->class(), true);
        }

        if ($registration === null) {
            throw FileCreatorException::registrationNotSupported($definition->type());
        }

        if ($target !== null) {
            $this->createOptionalModuleProvider($target);
        }

        $providerPath = $this->registrar->register($registration, $file->class(), $group);

        return new FileCreatorResult($file->path(), $file->class(), true, $providerPath, true);
    }

    private function createOptionalModuleProvider(ModuleFileCreatorTarget $target): void
    {
        $provider = $target->optionalProvider();

        if ($provider === null) {
            return;
        }

        if (!$this->files->exists($provider->path())) {
            $this->files->write($provider->path(), $provider->contents());
        }

        $this->registrar->register($provider->moduleRegistration(), $provider->class(), null);
    }

    private function requiresModuleOrPath(FileCreatorDefinition $definition): bool
    {
        $directory = $definition->defaultDirectory();

        return str_starts_with($directory, 'App/')
            && $directory !== 'App/Shared/Configs';
    }
}
