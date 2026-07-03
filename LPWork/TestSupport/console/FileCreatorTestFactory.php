<?php

declare(strict_types=1);

namespace Tests\support\console;

use LPWork\Console\FileCreators\FileCreator;
use LPWork\Console\FileCreators\FileCreatorDefinition;
use LPWork\Console\FileCreators\FileCreatorDefinitions;
use LPWork\Console\FileCreators\FileCreatorPathResolver;
use LPWork\Console\FileCreators\FileCreatorTemplateRenderer;
use LPWork\Console\FileCreators\ModuleFileCreatorTargetResolver;
use LPWork\Console\FileCreators\ProviderFileRegistrar;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\Modules\ModulePathResolver;
use RuntimeException;

final readonly class FileCreatorTestFactory
{
    public static function creator(string $basePath): FileCreator
    {
        $app = new Application($basePath);
        $files = new Filesystem();

        return new FileCreator(
            new FileCreatorPathResolver($app),
            new FileCreatorTemplateRenderer(),
            new ProviderFileRegistrar($app, $files),
            $files,
            new ModuleFileCreatorTargetResolver(new ModulePathResolver($app), $files, $app),
        );
    }

    public static function definition(string $type): FileCreatorDefinition
    {
        foreach (new FileCreatorDefinitions()->all() as $definition) {
            if ($definition->type() === $type) {
                return $definition;
            }
        }

        throw new RuntimeException("Missing file creator definition: {$type}");
    }
}
