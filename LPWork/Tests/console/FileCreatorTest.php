<?php

declare(strict_types=1);

use LPWork\Console\FileCreators\Exceptions\FileCreatorException;
use LPWork\Console\FileCreators\FileCreatorDefinition;
use LPWork\Console\FileCreators\FileCreatorDefinitions;
use LPWork\Console\FileCreators\ProviderFileRegistrar;
use LPWork\Console\Modules\ModuleCreator;
use LPWork\Filesystem\Filesystem;
use LPWork\Foundation\Application;
use LPWork\Foundation\Modules\ModulePathResolver;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\console\FileCreatorTestFactory;

afterEach(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('keeps file creator definitions grouped behind the stable aggregate', function (): void {
    $types = array_map(
        static fn(FileCreatorDefinition $definition): string => $definition->type(),
        new FileCreatorDefinitions()->all(),
    );

    expect($types)->toBe([
        'command',
        'console-middleware',
        'controller',
        'middleware',
        'route-definition',
        'event',
        'broadcast-event',
        'listener',
        'migration',
        'seeder',
        'validation-rule',
        'form-request',
        'notification',
        'broadcast-channel-provider',
        'view-engine',
        'view-extension',
        'config',
        'job',
        'health-check',
        'service-provider',
    ]);
});

it('requires a module or explicit path for module-owned generated files', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition('command');

    expect(fn() => $creator->create($definition, 'SyncUsers'))
        ->toThrow(FileCreatorException::class, 'make:command requires --module or an explicit --path.');
});

it('creates global config definitions from the explicit application config boundary', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition('config');

    $result = $creator->create($definition, 'Billing');

    expect($result->path())->toBe($environment->basePath() . '/App/Shared/Configs/BillingConfig.php')
        ->and($result->class())->toBe('App\Shared\Configs\BillingConfig')
        ->and(file_get_contents($result->path()))->toContain('namespace App\Shared\Configs;')
        ->and(file_get_contents($result->path()))->toContain("return 'billing';");
});

it('creates a file in a custom application path', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition('controller');

    $result = $creator->create($definition, 'Admin/Dashboard', path: 'App/Http/Controllers');

    expect($result->path())->toBe($environment->basePath() . '/App/Http/Controllers/Admin/DashboardController.php')
        ->and($result->class())->toBe('App\Http\Controllers\Admin\DashboardController')
        ->and(file_get_contents($result->path()))->toContain('namespace App\Http\Controllers\Admin;');
});

it('requires an explicit namespace when a custom path is outside known psr roots', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition('controller');

    expect(fn() => $creator->create($definition, 'Dashboard', path: 'src/Http'))
        ->toThrow(FileCreatorException::class, 'Cannot infer a namespace');
});

it('uses an explicit namespace for custom paths outside the default app tree', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition('controller');

    $result = $creator->create($definition, 'Dashboard', path: 'src/Http', namespace: 'Domain\Http');

    expect($result->path())->toBe($environment->basePath() . '/src/Http/DashboardController.php')
        ->and($result->class())->toBe('Domain\Http\DashboardController')
        ->and(file_get_contents($result->path()))->toContain('namespace Domain\Http;');
});

it('registers list based generated files in their provider', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('App/Routes/RoutesProvider.php', <<<'PHP'
        <?php

        declare(strict_types=1);

        namespace App\Routes;

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
                ];
            }
        }
        PHP);

    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition('route-definition');

    $result = $creator->create($definition, 'Api', path: 'App/Routes', register: true);

    expect($result->registered())->toBeTrue()
        ->and(file_get_contents($environment->basePath() . '/App/Routes/RoutesProvider.php'))
        ->toContain('use App\Routes\ApiRoutes;')
        ->toContain('ApiRoutes::class,');
});

it('registers grouped generated files under the selected provider group', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('App/Database/Migrations/MigrationsProvider.php', <<<'PHP'
        <?php

        declare(strict_types=1);

        namespace App\Database\Migrations;

        use LPWork\Database\Migrations\Contracts\Migration;
        use LPWork\Database\Migrations\Providers\MigrationsProvider as BaseMigrationsProvider;

        final class MigrationsProvider extends BaseMigrationsProvider
        {
            /**
             * @return array<string, list<class-string<Migration>>>
             */
            protected function migrations(): array
            {
                return [
                ];
            }
        }
        PHP);

    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition('migration');

    $creator->create($definition, 'CreateOrdersTable', path: 'App/Database/Migrations', register: true, group: 'analytics');

    expect(file_get_contents($environment->basePath() . '/App/Database/Migrations/MigrationsProvider.php'))
        ->toContain('use App\Database\Migrations\CreateOrdersTable;')
        ->toContain("'analytics' => [")
        ->toContain('CreateOrdersTable::class,');
});

it('creates generated files inside a targeted module', function (): void {
    $environment = ApplicationTestEnvironment::create();
    createFileCreatorTestModule($environment->basePath(), 'Blog');
    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition('controller');

    $result = $creator->create($definition, 'Admin/Dashboard', module: 'Blog');

    expect($result->path())->toBe($environment->basePath() . '/App/Modules/Blog/Controllers/Admin/DashboardController.php')
        ->and($result->class())->toBe('App\Modules\Blog\Controllers\Admin\DashboardController')
        ->and(file_get_contents($result->path()))->toContain('namespace App\Modules\Blog\Controllers\Admin;');
});

it('generates typed starter files without placeholder comment bodies', function (
    string $type,
    string $name,
    string $path,
    string $expectedContent,
): void {
    $environment = ApplicationTestEnvironment::create();
    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition($type);

    $result = $creator->create($definition, $name, path: $path);
    $contents = file_get_contents($result->path());
    expect($contents)->not->toBeFalse();
    $contents = (string) $contents;

    expect((bool) preg_match('/^\s*\/\/\s*$/m', $contents))->toBeFalse()
        ->and($contents)->toContain($expectedContent);
})->with([
    'command output' => ['command', 'SyncUsers', 'App/Console/Commands', "\$output->writeln('SyncUsersCommand executed.');"],
    'controller response' => ['controller', 'Dashboard', 'App/Controllers', "return HttpResponse::html('DashboardController');"],
    'route starter' => ['route-definition', 'Api', 'App/Routes', "->get('/api', static fn(): HttpResponse => HttpResponse::html('ApiRoutes'))"],
    'event object' => ['event', 'PostPublished', 'App/Events', 'final readonly class PostPublished'],
    'broadcast event channel' => ['broadcast-event', 'OrderCreated', 'App/Events', "return ['order-created'];"],
    'migration schema builder' => ['migration', 'CreatePostsTable', 'App/Database/Migrations', "private const TABLE = 'posts';"],
    'notification body' => ['notification', 'PostPublished', 'App/Notifications', "->text('PostPublishedNotification notification.')"],
    'broadcast provider channel' => ['broadcast-channel-provider', 'Realtime', 'App/Broadcasting', "\$channels->public('realtime');"],
    'view engine reads path' => ['view-engine', 'Markdown', 'App/View/Engines', 'file_get_contents($path);'],
]);

it('registers list based generated files in a targeted module provider', function (): void {
    $environment = ApplicationTestEnvironment::create();
    createFileCreatorTestModule($environment->basePath(), 'Blog');
    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition('command');

    $result = $creator->create($definition, 'SyncUsers', register: true, module: 'Blog');

    expect($result->path())->toBe($environment->basePath() . '/App/Modules/Blog/Console/Commands/SyncUsersCommand.php')
        ->and($result->class())->toBe('App\Modules\Blog\Console\Commands\SyncUsersCommand')
        ->and($result->registered())->toBeTrue()
        ->and($result->providerPath())->toBe($environment->basePath() . '/App/Modules/Blog/Console/ConsoleProvider.php');

    expect(file_get_contents($environment->basePath() . '/App/Modules/Blog/Console/ConsoleProvider.php'))
        ->toContain('use App\Modules\Blog\Console\Commands\SyncUsersCommand;')
        ->toContain('SyncUsersCommand::class,');
});

it('registers grouped generated files in a targeted module provider', function (): void {
    $environment = ApplicationTestEnvironment::create();
    createFileCreatorTestModule($environment->basePath(), 'Blog');
    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition('migration');

    $result = $creator->create($definition, 'CreateInvoicesTable', register: true, group: 'billing', module: 'Blog');

    expect($result->path())->toBe($environment->basePath() . '/App/Modules/Blog/Database/Migrations/CreateInvoicesTable.php')
        ->and($result->class())->toBe('App\Modules\Blog\Database\Migrations\CreateInvoicesTable')
        ->and($result->providerPath())->toBe($environment->basePath() . '/App/Modules/Blog/Database/Migrations/MigrationsProvider.php');

    expect(file_get_contents($environment->basePath() . '/App/Modules/Blog/Database/Migrations/MigrationsProvider.php'))
        ->toContain('use App\Modules\Blog\Database\Migrations\CreateInvoicesTable;')
        ->toContain("'billing' => [")
        ->toContain('CreateInvoicesTable::class,');
});

it('maps every module-aware file creator into the targeted module', function (
    string $type,
    string $name,
    string $expectedPath,
    string $expectedClass,
): void {
    $environment = ApplicationTestEnvironment::create();
    createFileCreatorTestModule($environment->basePath(), 'Blog');
    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition($type);

    $result = $creator->create($definition, $name, module: 'Blog');
    $namespaceSegments = explode('\\', $expectedClass);
    array_pop($namespaceSegments);

    expect($result->path())->toBe($environment->basePath() . '/' . $expectedPath)
        ->and($result->class())->toBe($expectedClass)
        ->and(file_get_contents($result->path()))->toContain('namespace ' . implode('\\', $namespaceSegments) . ';');
})->with([
    'command' => ['command', 'SyncUsers', 'App/Modules/Blog/Console/Commands/SyncUsersCommand.php', 'App\Modules\Blog\Console\Commands\SyncUsersCommand'],
    'controller' => ['controller', 'Admin/Dashboard', 'App/Modules/Blog/Controllers/Admin/DashboardController.php', 'App\Modules\Blog\Controllers\Admin\DashboardController'],
    'middleware' => ['middleware', 'Authenticate', 'App/Modules/Blog/Middleware/AuthenticateMiddleware.php', 'App\Modules\Blog\Middleware\AuthenticateMiddleware'],
    'console middleware' => ['console-middleware', 'AuditCommand', 'App/Modules/Blog/Console/Middleware/AuditCommandMiddleware.php', 'App\Modules\Blog\Console\Middleware\AuditCommandMiddleware'],
    'route definition' => ['route-definition', 'Api', 'App/Modules/Blog/Routes/ApiRoutes.php', 'App\Modules\Blog\Routes\ApiRoutes'],
    'event' => ['event', 'PostPublished', 'App/Modules/Blog/Events/PostPublished.php', 'App\Modules\Blog\Events\PostPublished'],
    'broadcast event' => ['broadcast-event', 'PostPublished', 'App/Modules/Blog/Events/PostPublishedEvent.php', 'App\Modules\Blog\Events\PostPublishedEvent'],
    'listener' => ['listener', 'SendPostPublishedMail', 'App/Modules/Blog/Listeners/SendPostPublishedMailListener.php', 'App\Modules\Blog\Listeners\SendPostPublishedMailListener'],
    'migration' => ['migration', 'CreatePostsTable', 'App/Modules/Blog/Database/Migrations/CreatePostsTable.php', 'App\Modules\Blog\Database\Migrations\CreatePostsTable'],
    'seeder' => ['seeder', 'BlogDemo', 'App/Modules/Blog/Database/Seeders/BlogDemoSeeder.php', 'App\Modules\Blog\Database\Seeders\BlogDemoSeeder'],
    'job' => ['job', 'PublishPost', 'App/Modules/Blog/Jobs/PublishPostJob.php', 'App\Modules\Blog\Jobs\PublishPostJob'],
    'validation rule' => ['validation-rule', 'Slug', 'App/Modules/Blog/Validation/Rules/SlugRule.php', 'App\Modules\Blog\Validation\Rules\SlugRule'],
    'form request' => ['form-request', 'StorePost', 'App/Modules/Blog/Validation/Requests/StorePostRequest.php', 'App\Modules\Blog\Validation\Requests\StorePostRequest'],
    'config' => ['config', 'FeatureFlags', 'App/Modules/Blog/Configs/FeatureFlagsConfig.php', 'App\Modules\Blog\Configs\FeatureFlagsConfig'],
    'notification' => ['notification', 'PostPublished', 'App/Modules/Blog/Notifications/PostPublishedNotification.php', 'App\Modules\Blog\Notifications\PostPublishedNotification'],
    'broadcast channel provider' => ['broadcast-channel-provider', 'Realtime', 'App/Modules/Blog/Broadcasting/RealtimeProvider.php', 'App\Modules\Blog\Broadcasting\RealtimeProvider'],
    'health check' => ['health-check', 'SearchCluster', 'App/Modules/Blog/Health/Checks/SearchClusterHealthCheck.php', 'App\Modules\Blog\Health\Checks\SearchClusterHealthCheck'],
    'view engine' => ['view-engine', 'Markdown', 'App/Modules/Blog/View/Engines/MarkdownViewEngine.php', 'App\Modules\Blog\View\Engines\MarkdownViewEngine'],
    'view extension' => ['view-extension', 'Money', 'App/Modules/Blog/View/Extensions/MoneyViewExtension.php', 'App\Modules\Blog\View\Extensions\MoneyViewExtension'],
    'service provider' => ['service-provider', 'Billing', 'App/Modules/Blog/Providers/BillingProvider.php', 'App\Modules\Blog\Providers\BillingProvider'],
]);

it('registers module config definitions in the module configs provider', function (): void {
    $environment = ApplicationTestEnvironment::create();
    createFileCreatorTestModule($environment->basePath(), 'Blog');
    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition('config');

    $result = $creator->create($definition, 'FeatureFlags', register: true, module: 'Blog');

    expect($result->path())->toBe($environment->basePath() . '/App/Modules/Blog/Configs/FeatureFlagsConfig.php')
        ->and($result->class())->toBe('App\Modules\Blog\Configs\FeatureFlagsConfig')
        ->and($result->providerPath())->toBe($environment->basePath() . '/App/Modules/Blog/Configs/ConfigsProvider.php')
        ->and(file_get_contents($result->path()))->toContain("return 'feature-flags';");

    expect(file_get_contents($environment->basePath() . '/App/Modules/Blog/Configs/ConfigsProvider.php'))
        ->toContain('use App\Modules\Blog\Configs\FeatureFlagsConfig;')
        ->toContain('FeatureFlagsConfig::class,');
});

it('rejects module targeting when the module is missing or mixed with custom paths', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition('controller');

    expect(fn() => $creator->create($definition, 'Dashboard', module: 'Missing'))
        ->toThrow(FileCreatorException::class, 'Module does not exist: ' . $environment->basePath() . '/App/Modules/Missing');

    createFileCreatorTestModule($environment->basePath(), 'Blog');

    expect(fn() => $creator->create($definition, 'Dashboard', path: 'App/Http', module: 'Blog'))
        ->toThrow(FileCreatorException::class, 'The --module option cannot be combined with --path or --namespace.');
});

it('creates form request job broadcast event console middleware and health check files', function (string $type, string $name, string $expectedPath, string $expectedContent): void {
    $environment = ApplicationTestEnvironment::create();
    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition($type);

    $result = $creator->create($definition, $name, path: dirname($expectedPath));

    expect($result->path())->toBe($environment->basePath() . '/' . $expectedPath)
        ->and(file_get_contents($result->path()))->toContain($expectedContent);
})->with([
    'form request' => ['form-request', 'StorePost', 'App/Validation/Requests/StorePostRequest.php', 'extends FormRequest'],
    'job' => ['job', 'SendDigest', 'App/Jobs/SendDigestJob.php', 'public function handle(): void'],
    'broadcast event' => ['broadcast-event', 'OrderCreated', 'App/Events/OrderCreatedEvent.php', 'implements BroadcastableEvent'],
    'console middleware' => ['console-middleware', 'AuditCommand', 'App/Console/Middleware/AuditCommandMiddleware.php', 'implements ConsoleMiddleware'],
    'health check' => ['health-check', 'SearchCluster', 'App/Health/Checks/SearchClusterHealthCheck.php', 'implements HealthCheck'],
]);

it('registers a generated view engine in the application view provider list', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('App/View/ViewProvider.php', <<<'PHP'
        <?php

        declare(strict_types=1);

        namespace App\View;

        use Closure;
        use LPWork\View\Contracts\ViewEngine;
        use LPWork\View\Providers\PhpViewEngineProvider;

        final class ViewProvider extends PhpViewEngineProvider
        {
            /**
             * @return list<class-string<ViewEngine>>
             */
            protected function viewEngines(): array
            {
                return [
                ];
            }

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
        PHP);

    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition('view-engine');

    $creator->create($definition, 'Markdown', path: 'App/View/Engines', register: true);

    expect(file_get_contents($environment->basePath() . '/App/View/ViewProvider.php'))
        ->toContain('use App\View\Engines\MarkdownViewEngine;')
        ->toContain('MarkdownViewEngine::class,');
});

it('registers generated application provider style creators in the application provider', function (
    string $type,
    string $name,
    string $path,
    string $expectedUse,
    string $expectedClass,
): void {
    $environment = ApplicationTestEnvironment::create();
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

    $creator = FileCreatorTestFactory::creator($environment->basePath());
    $definition = FileCreatorTestFactory::definition($type);

    $creator->create($definition, $name, path: $path, register: true);

    expect(file_get_contents($environment->basePath() . '/App/AppServiceProvider.php'))
        ->toContain($expectedUse)
        ->toContain($expectedClass . '::class,');
})->with([
    'broadcast channel provider' => ['broadcast-channel-provider', 'Realtime', 'App/Broadcasting', 'use App\Broadcasting\RealtimeProvider;', 'RealtimeProvider'],
    'view extension' => ['view-extension', 'Money', 'App/View/Extensions', 'use App\View\Extensions\MoneyViewExtension;', 'MoneyViewExtension'],
]);

function createFileCreatorTestModule(string $basePath, string $name): void
{
    $app = new Application($basePath);
    $files = new Filesystem();
    $creator = new ModuleCreator(
        new ModulePathResolver($app),
        $files,
        new ProviderFileRegistrar($app, $files),
        $app,
    );

    $creator->create($name, register: false);
}
