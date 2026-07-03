<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Foundation\Exceptions\ProviderFileNotFoundException;
use LPWork\Foundation\ServiceProvider as BaseServiceProvider;
use Tests\support\container\SimpleService;
use Tests\support\foundation\ProviderTestFiles;

afterAll(function (): void {
    ProviderTestFiles::removeDirectories();
});

it('defines the service provider contract', function (): void {
    $provider = new class implements ServiceProvider {
        public function register(Container $container): void
        {
            $container->instance(SimpleService::class, new SimpleService());
        }
    };

    $container = new Container();
    $provider->register($container);

    expect($container->make(SimpleService::class))->toBeInstanceOf(SimpleService::class);
});

it('loads service provider files with access to the container', function (): void {
    $path = ProviderTestFiles::createFile(
        'services.php',
        <<<'PHP'
            <?php

            declare(strict_types=1);

            use Tests\support\container\SimpleService;

            $container->instance(SimpleService::class, new SimpleService());
            PHP,
    );

    $provider = new class ($path) extends BaseServiceProvider {
        public function __construct(
            private readonly string $path,
        ) {}

        public function register(Container $container): void
        {
            $this->load($this->path, $container);
        }
    };

    $container = new Container();
    $provider->register($container);

    expect($container->make(SimpleService::class))->toBeInstanceOf(SimpleService::class);
});

it('throws when a provider file does not exist', function (): void {
    $path = ProviderTestFiles::createDirectory() . '/missing.php';

    $provider = new class ($path) extends BaseServiceProvider {
        public function __construct(
            private readonly string $path,
        ) {}

        public function register(Container $container): void
        {
            $this->load($this->path, $container);
        }
    };

    expect(function () use ($provider): void {
        $provider->register(new Container());
    })->toThrow(ProviderFileNotFoundException::class, sprintf('Provider file does not exist: %s', $path));
});
