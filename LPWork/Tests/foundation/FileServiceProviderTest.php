<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\Foundation\Application;
use LPWork\Foundation\Providers\FileServiceProvider;
use Tests\support\container\SimpleService;
use Tests\support\foundation\ProviderTestFiles;

afterAll(function (): void {
    ProviderTestFiles::removeDirectories();
});

it('loads files relative to the application base path', function (): void {
    $basePath = ProviderTestFiles::createDirectory();
    ProviderTestFiles::createFile(
        'bootstrap/services.php',
        <<<'PHP'
            <?php

            declare(strict_types=1);

            use Tests\support\container\SimpleService;

            $container->instance(SimpleService::class, new SimpleService());
            PHP,
        $basePath,
    );

    $provider = new class (new Application($basePath)) extends FileServiceProvider {
        /**
         * @return list<string>
         */
        protected function loadFiles(): array
        {
            return [
                '/bootstrap/services.php',
            ];
        }
    };

    $container = new Container();
    $provider->register($container);

    expect($container->make(SimpleService::class))->toBeInstanceOf(SimpleService::class);
});
