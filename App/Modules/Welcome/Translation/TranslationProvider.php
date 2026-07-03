<?php

declare(strict_types=1);

namespace App\Modules\Welcome\Translation;

use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Foundation\Application;
use LPWork\Foundation\ServiceProvider;
use LPWork\Translation\TranslationNamespaceRegistry;

final class TranslationProvider extends ServiceProvider
{
    public function register(Container $container): void
    {
        $app = $container->make(Application::class);
        $translations = $container->make(TranslationNamespaceRegistry::class);

        if (!$app instanceof Application) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject(Application::class);
        }

        if (!$translations instanceof TranslationNamespaceRegistry) {
            throw CannotResolveDependencyException::factoryDidNotReturnObject(TranslationNamespaceRegistry::class);
        }

        $translations->add('welcome', $app->basePath('App/Modules/Welcome/lang'));
    }
}
