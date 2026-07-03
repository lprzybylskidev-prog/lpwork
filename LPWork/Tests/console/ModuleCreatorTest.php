<?php

declare(strict_types=1);

use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Config\Contracts\ConfigDefinitionProvider;
use LPWork\Console\FileCreators\Exceptions\FileCreatorException;
use LPWork\Console\FileCreators\ProviderFileRegistrar;
use LPWork\Console\Modules\ModuleCreator;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Foundation\Modules\ModulePathResolver;
use LPWork\Foundation\Providers\FoundationServiceProvider;
use LPWork\Frontend\AssetEntryRegistry;
use LPWork\Routing\Router;
use LPWork\Translation\TranslationNamespaceRegistry;
use LPWork\View\PhpViewEngineExtensions;
use LPWork\View\ViewNamespaceRegistry;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\testing\Architecture\ArchitectureAssertions;

afterEach(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('creates a module skeleton and registers it in the application provider', function (): void {
    $environment = ApplicationTestEnvironment::create();
    writeAppServiceProvider($environment);
    $creator = moduleCreator($environment->basePath());

    $result = $creator->create('Blog');

    expect($result->modulePath())->toBe($environment->basePath() . '/App/Modules/Blog')
        ->and($result->serviceProviderClass())->toBe('App\Modules\Blog\BlogServiceProvider')
        ->and($result->registered())->toBeTrue()
        ->and($result->registeredProviderPath())->toBe($environment->basePath() . '/App/AppServiceProvider.php');

    $serviceProvider = file_get_contents($environment->basePath() . '/App/Modules/Blog/BlogServiceProvider.php');

    if (!is_string($serviceProvider)) {
        throw new RuntimeException('Generated service provider could not be read.');
    }

    expect($serviceProvider)
        ->toContain('namespace App\Modules\Blog;')
        ->toContain('use App\Modules\Blog\Assets\AssetsProvider;')
        ->toContain('use App\Modules\Blog\Configs\ConfigsProvider;')
        ->toContain('use App\Modules\Blog\View\ViewProvider;')
        ->toContain('AssetsProvider::class,')
        ->toContain('ConfigsProvider::class,')
        ->toContain('RoutesProvider::class,')
        ->toContain('ViewProvider::class,');

    expect($serviceProvider)->not->toContain('ConsoleProvider::class,');
    expect($serviceProvider)->not->toContain('MigrationsProvider::class,');
    expect($serviceProvider)->not->toContain('ValidationProvider::class,');

    expect(file_get_contents($environment->basePath() . '/App/Modules/Blog/Configs/BlogConfig.php'))
        ->toContain('namespace App\Modules\Blog\Configs;')
        ->toContain("return 'blog';");

    expect(file_get_contents($environment->basePath() . '/App/Modules/Blog/View/ViewProvider.php'))
        ->toContain("'blog' => 'App/Modules/Blog/resources/views'");

    expect(file_get_contents($environment->basePath() . '/App/Modules/Blog/Translation/TranslationProvider.php'))
        ->toContain("\$translations->add('blog', \$app->basePath('App/Modules/Blog/lang'));");

    expect(file_get_contents($environment->basePath() . '/App/Modules/Blog/Assets/AssetsProvider.php'))
        ->toContain('namespace App\Modules\Blog\Assets;')
        ->toContain("'blog::app' => 'App/Modules/Blog/resources/frontend/app.ts'");

    expect(file_get_contents($environment->basePath() . '/App/Modules/Blog/Routes/WebRoutes.php'))
        ->toContain('namespace App\Modules\Blog\Routes;')
        ->toContain('public function register(Router $router): void');

    expect(file_get_contents($environment->basePath() . '/App/Modules/Blog/resources/views/index.php'))
        ->toContain('<h1>Blog</h1>');

    expect(file_get_contents($environment->basePath() . '/App/Modules/Blog/resources/frontend/app.ts'))
        ->toContain("import './app.css';")
        ->and(file_get_contents($environment->basePath() . '/App/Modules/Blog/resources/frontend/app.css'))
        ->toContain('body {}');

    expect(file_get_contents($environment->basePath() . '/App/Modules/Blog/tests/backend/BlogModuleTest.php'))
        ->toContain('use App\Modules\Blog\BlogServiceProvider;')
        ->toContain('expect(BlogServiceProvider::class)->toImplement(ServiceProvider::class);');

    expect(file_get_contents($environment->basePath() . '/App/Modules/Blog/tests/frontend/app.test.ts'))
        ->toContain("describe('blog frontend assets'");

    expect($environment->basePath() . '/App/Modules/Blog/Console/ConsoleProvider.php')->not->toBeFile()
        ->and($environment->basePath() . '/App/Modules/Blog/Database/Migrations/MigrationsProvider.php')->not->toBeFile()
        ->and($environment->basePath() . '/App/Modules/Blog/Database/Seeders/SeedersProvider.php')->not->toBeFile()
        ->and($environment->basePath() . '/App/Modules/Blog/Events/EventsProvider.php')->not->toBeFile()
        ->and($environment->basePath() . '/App/Modules/Blog/Schedule/ScheduleProvider.php')->not->toBeFile()
        ->and($environment->basePath() . '/App/Modules/Blog/Validation/ValidationProvider.php')->not->toBeFile();

    expect(file_get_contents($environment->basePath() . '/App/AppServiceProvider.php'))
        ->toContain('use App\Modules\Blog\BlogServiceProvider;')
        ->toContain('BlogServiceProvider::class,');
});

it('creates a nested module without provider registration when requested', function (): void {
    $environment = ApplicationTestEnvironment::create();
    writeAppServiceProvider($environment);
    $creator = moduleCreator($environment->basePath());

    $result = $creator->create('Admin/Reports', register: false, frontend: false);

    expect($result->modulePath())->toBe($environment->basePath() . '/App/Modules/Admin/Reports')
        ->and($result->serviceProviderClass())->toBe('App\Modules\Admin\Reports\ReportsServiceProvider')
        ->and($result->registered())->toBeFalse()
        ->and($result->registeredProviderPath())->toBeNull();

    $reportsProvider = file_get_contents($environment->basePath() . '/App/Modules/Admin/Reports/ReportsServiceProvider.php');

    if (!is_string($reportsProvider)) {
        throw new RuntimeException('Generated reports provider could not be read.');
    }

    expect($reportsProvider)
        ->toContain('namespace App\Modules\Admin\Reports;');
    expect($reportsProvider)->not->toContain('AssetsProvider::class,');

    expect(file_get_contents($environment->basePath() . '/App/AppServiceProvider.php'))
        ->not->toContain('ReportsServiceProvider::class,');

    expect($environment->basePath() . '/App/Modules/Admin/Reports/Assets/AssetsProvider.php')->not->toBeFile()
        ->and($environment->basePath() . '/App/Modules/Admin/Reports/resources/frontend/app.ts')->not->toBeFile()
        ->and($environment->basePath() . '/App/Modules/Admin/Reports/resources/frontend/app.css')->not->toBeFile()
        ->and($environment->basePath() . '/App/Modules/Admin/Reports/tests/backend/ReportsModuleTest.php')->toBeFile()
        ->and($environment->basePath() . '/App/Modules/Admin/Reports/tests/frontend/app.test.ts')->not->toBeFile();
});

it('refuses to overwrite an existing module directory', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('App/Modules/Blog/existing.txt', 'already here');
    $creator = moduleCreator($environment->basePath());

    expect(fn() => $creator->create('Blog', register: false))
        ->toThrow(FileCreatorException::class, 'File already exists: ' . $environment->basePath() . '/App/Modules/Blog');
});

it('loads generated module declarations through their module providers', function (): void {
    $environment = ApplicationTestEnvironment::create();
    writeAppServiceProvider($environment);
    $creator = moduleCreator($environment->basePath());

    $creator->create('Blog');

    $autoload = moduleAutoloader($environment->basePath(), 'Blog');
    spl_autoload_register($autoload, prepend: true);

    try {
        $app = new Application($environment->basePath());
        $app->register(new FoundationServiceProvider($app));
        $app->container()->instance(Router::class, new Router());
        $app->container()->instance(AssetEntryRegistry::class, new AssetEntryRegistry());
        $app->container()->instance(ViewNamespaceRegistry::class, new ViewNamespaceRegistry());
        $app->container()->instance(PhpViewEngineExtensions::class, new PhpViewEngineExtensions());
        $app->container()->instance(TranslationNamespaceRegistry::class, new TranslationNamespaceRegistry());

        $assetProviderClass = generatedModuleClass('Blog', 'Assets', 'AssetsProvider');
        $configProviderClass = generatedModuleClass('Blog', 'Configs', 'ConfigsProvider');
        $configClass = generatedModuleClass('Blog', 'Configs', 'BlogConfig');
        $viewProviderClass = generatedModuleClass('Blog', 'View', 'ViewProvider');
        $translationProviderClass = generatedModuleClass('Blog', 'Translation', 'TranslationProvider');
        $routesProviderClass = generatedModuleClass('Blog', 'Routes', 'RoutesProvider');

        $configProvider = generatedConfigDefinitionProvider($configProviderClass);
        $config = generatedConfigDefinition($configClass);

        expect($configProvider->configDefinitions())->toBe([$configClass])
            ->and($config->key())->toBe('blog');

        $app->register(generatedServiceProvider($assetProviderClass));
        $app->register(generatedServiceProvider($viewProviderClass));
        $app->register(generatedServiceProvider($translationProviderClass));
        $app->register(generatedServiceProvider($routesProviderClass));

        $assetEntries = $app->container()->make(AssetEntryRegistry::class);
        $viewNamespaces = $app->container()->make(ViewNamespaceRegistry::class);
        $translationNamespaces = $app->container()->make(TranslationNamespaceRegistry::class);

        expect($assetEntries)->toBeInstanceOf(AssetEntryRegistry::class)
            ->and($viewNamespaces)->toBeInstanceOf(ViewNamespaceRegistry::class)
            ->and($translationNamespaces)->toBeInstanceOf(TranslationNamespaceRegistry::class);

        if ($assetEntries instanceof AssetEntryRegistry && $viewNamespaces instanceof ViewNamespaceRegistry && $translationNamespaces instanceof TranslationNamespaceRegistry) {
            expect($assetEntries->get('blog::app')?->sourcePath())->toBe('App/Modules/Blog/resources/frontend/app.ts')
                ->and($viewNamespaces->paths('blog'))->toBe(['App/Modules/Blog/resources/views'])
                ->and($translationNamespaces->all())->toHaveKey('blog', $environment->basePath() . '/App/Modules/Blog/lang');
        }

        ArchitectureAssertions::assertApplicationUsesModuleFirstShape([$environment->basePath()]);
    } finally {
        spl_autoload_unregister($autoload);
    }
});

function moduleCreator(string $basePath): ModuleCreator
{
    $app = new Application($basePath);
    $files = new Filesystem();

    return new ModuleCreator(
        new ModulePathResolver($app),
        $files,
        new ProviderFileRegistrar($app, $files),
        $app,
    );
}

function writeAppServiceProvider(ApplicationTestEnvironment $environment): void
{
    $environment->writeFile('App/AppServiceProvider.php', <<<'PHP'
        <?php

        declare(strict_types=1);

        namespace App;

        use LPWork\Foundation\Contracts\ServiceProvider;
        use LPWork\Foundation\Providers\ProviderServiceProvider;

        final class AppServiceProvider extends ProviderServiceProvider
        {
            /**
             * @return list<class-string<ServiceProvider>>
             */
            protected function serviceProviders(): array
            {
                return [
                ];
            }
        }
        PHP);
}

function moduleAutoloader(string $basePath, string $module): Closure
{
    $prefix = 'App\\Modules\\' . $module . '\\';
    $root = $basePath . '/App/Modules/' . $module . '/';

    return static function (string $class) use ($prefix, $root): void {
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $path = $root . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

        if (is_file($path)) {
            require $path;
        }
    };
}

function generatedModuleClass(string $module, string $namespace, string $class): string
{
    return 'App\\Modules\\' . $module . '\\' . $namespace . '\\' . $class;
}

function generatedConfigDefinitionProvider(string $class): ConfigDefinitionProvider
{
    $object = generatedObject($class);

    if (!$object instanceof ConfigDefinitionProvider) {
        throw new RuntimeException("Generated class is not a config definition provider: {$class}");
    }

    return $object;
}

function generatedConfigDefinition(string $class): ConfigDefinition
{
    $object = generatedObject($class);

    if (!$object instanceof ConfigDefinition) {
        throw new RuntimeException("Generated class is not a config definition: {$class}");
    }

    return $object;
}

function generatedServiceProvider(string $class): ServiceProvider
{
    $object = generatedObject($class);

    if (!$object instanceof ServiceProvider) {
        throw new RuntimeException("Generated class is not a service provider: {$class}");
    }

    return $object;
}

function generatedObject(string $class): object
{
    if (!class_exists($class)) {
        throw new RuntimeException("Generated class does not exist: {$class}");
    }

    return new $class();
}
