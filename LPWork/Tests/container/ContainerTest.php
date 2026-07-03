<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\Container\Exceptions\CannotResolveDependencyException;
use LPWork\Container\Exceptions\CircularDependencyException;
use LPWork\Container\Exceptions\InvalidBindingException;
use Tests\support\container\ActionController;
use Tests\support\container\AlternativeBoundService;
use Tests\support\container\BoundService;
use Tests\support\container\CircularA;
use Tests\support\container\ContextualDependencyService;
use Tests\support\container\ContractDependencyService;
use Tests\support\container\DefaultContractDependencyService;
use Tests\support\container\DependentService;
use Tests\support\container\MultiDependencyService;
use Tests\support\container\NestedDependencyService;
use Tests\support\container\OptionalParameterService;
use Tests\support\container\ScalarDependencyService;
use Tests\support\container\ServiceContract;
use Tests\support\container\SimpleService;
use Tests\support\foundation\ProviderTestFiles;

afterAll(function (): void {
    ProviderTestFiles::removeDirectories();
});

it('can be created', function (): void {
    expect(new Container())->toBeInstanceOf(Container::class);
});

it('makes unbound instantiable classes', function (): void {
    $container = new Container();

    expect($container->make(SimpleService::class))->toBeInstanceOf(SimpleService::class)
        ->and($container->get(SimpleService::class))->toBeInstanceOf(SimpleService::class);
});

it('creates a fresh instance for normal bindings', function (): void {
    $container = new Container();

    $container->bind(ServiceContract::class, BoundService::class);

    $first = $container->make(ServiceContract::class);
    $second = $container->make(ServiceContract::class);

    expect($first)->toBeInstanceOf(BoundService::class)
        ->and($second)->toBeInstanceOf(BoundService::class)
        ->and($first)->not->toBe($second);
});

it('creates a fresh instance from factory bindings', function (): void {
    $container = new Container();

    $container->bind(ScalarDependencyService::class, static fn(Container $container): ScalarDependencyService => new ScalarDependencyService('factory'));

    $first = $container->make(ScalarDependencyService::class);
    $second = $container->make(ScalarDependencyService::class);

    expect($first)->toBeInstanceOf(ScalarDependencyService::class)
        ->and($second)->toBeInstanceOf(ScalarDependencyService::class)
        ->and($first)->not->toBe($second);

    if ($first instanceof ScalarDependencyService) {
        expect($first->name)->toBe('factory');
    }
});

it('creates one instance for singleton bindings', function (): void {
    $container = new Container();

    $container->singleton(ServiceContract::class, BoundService::class);

    $first = $container->make(ServiceContract::class);
    $second = $container->make(ServiceContract::class);

    expect($first)->toBeInstanceOf(BoundService::class)
        ->and($first)->toBe($second);
});

it('creates one instance from singleton factory bindings', function (): void {
    $container = new Container();

    $container->singleton(ScalarDependencyService::class, static fn(Container $container): ScalarDependencyService => new ScalarDependencyService('singleton'));

    $first = $container->make(ScalarDependencyService::class);
    $second = $container->make(ScalarDependencyService::class);

    expect($first)->toBeInstanceOf(ScalarDependencyService::class)
        ->and($first)->toBe($second);
});

it('returns registered object instances', function (): void {
    $container = new Container();
    $service = new SimpleService();

    $container->instance(SimpleService::class, $service);

    expect($container->make(SimpleService::class))->toBe($service)
        ->and($container->get(SimpleService::class))->toBe($service);
});

it('reports whether entries can be resolved', function (): void {
    $container = new Container();

    $container->bind(ServiceContract::class, BoundService::class);

    expect($container->has(SimpleService::class))->toBeTrue()
        ->and($container->has(ServiceContract::class))->toBeTrue()
        ->and($container->has('Tests\\support\\container\\MissingService'))->toBeFalse();
});

it('reports whether entries are explicitly bound or registered as instances', function (): void {
    $container = new Container();

    $container->bind(ServiceContract::class, BoundService::class);
    $container->instance(BoundService::class, new BoundService());

    expect($container->isBound(SimpleService::class))->toBeFalse()
        ->and($container->isBound(ServiceContract::class))->toBeTrue()
        ->and($container->isBound(BoundService::class))->toBeTrue();
});

it('resolves aliases to their abstract entries', function (): void {
    $container = new Container();

    $container->singleton(ServiceContract::class, BoundService::class);
    $container->alias(ServiceContract::class, 'service');

    expect($container->has('service'))->toBeTrue()
        ->and($container->make('service'))->toBe($container->make(ServiceContract::class));
});

it('lets registered instances override existing bindings', function (): void {
    $container = new Container();
    $service = new BoundService();

    $container->bind(ServiceContract::class, BoundService::class);
    $container->instance(ServiceContract::class, $service);

    expect($container->make(ServiceContract::class))->toBe($service);
});

it('replaces cached singleton instances when binding is changed', function (): void {
    $container = new Container();

    $container->singleton(ServiceContract::class, BoundService::class);
    $first = $container->make(ServiceContract::class);

    $container->bind(ServiceContract::class, BoundService::class);
    $second = $container->make(ServiceContract::class);

    expect($first)->not->toBe($second);
});

it('makes classes with optional scalar constructor parameters', function (): void {
    $container = new Container();

    $service = $container->make(OptionalParameterService::class);

    expect($service)->toBeInstanceOf(OptionalParameterService::class);
});

it('uses default values when optional class dependencies have no binding', function (): void {
    $container = new Container();

    $service = $container->make(DefaultContractDependencyService::class);

    expect($service)->toBeInstanceOf(DefaultContractDependencyService::class);

    if ($service instanceof DefaultContractDependencyService) {
        expect($service->service)->toBeNull();
    }
});

it('autowires concrete constructor dependencies', function (): void {
    $container = new Container();

    $service = $container->make(DependentService::class);

    expect($service)->toBeInstanceOf(DependentService::class);

    if ($service instanceof DependentService) {
        expect($service->service)->toBeInstanceOf(SimpleService::class);
    }
});

it('autowires nested constructor dependencies', function (): void {
    $container = new Container();

    $service = $container->make(NestedDependencyService::class);

    expect($service)->toBeInstanceOf(NestedDependencyService::class);

    if ($service instanceof NestedDependencyService) {
        expect($service->service)->toBeInstanceOf(DependentService::class)
            ->and($service->service->service)->toBeInstanceOf(SimpleService::class);
    }
});

it('autowires multiple constructor dependencies', function (): void {
    $container = new Container();

    $service = $container->make(MultiDependencyService::class);

    expect($service)->toBeInstanceOf(MultiDependencyService::class);

    if ($service instanceof MultiDependencyService) {
        expect($service->first)->toBeInstanceOf(SimpleService::class)
            ->and($service->second)->toBeInstanceOf(BoundService::class);
    }
});

it('autowires dependencies through bindings', function (): void {
    $container = new Container();

    $container->bind(ServiceContract::class, BoundService::class);

    $service = $container->make(ContractDependencyService::class);

    expect($service)->toBeInstanceOf(ContractDependencyService::class);

    if ($service instanceof ContractDependencyService) {
        expect($service->service)->toBeInstanceOf(BoundService::class);
    }
});

it('uses contextual bindings for a specific concrete class', function (): void {
    $container = new Container();

    $container->bind(ServiceContract::class, BoundService::class);
    $container->when(ContextualDependencyService::class)
        ->needs(ServiceContract::class)
        ->give(AlternativeBoundService::class);

    $default = $container->make(ContractDependencyService::class);
    $contextual = $container->make(ContextualDependencyService::class);

    expect($default)->toBeInstanceOf(ContractDependencyService::class)
        ->and($contextual)->toBeInstanceOf(ContextualDependencyService::class);

    if ($default instanceof ContractDependencyService && $contextual instanceof ContextualDependencyService) {
        expect($default->service)->toBeInstanceOf(BoundService::class)
            ->and($contextual->service)->toBeInstanceOf(AlternativeBoundService::class);
    }
});

it('resolves tagged entries in registration order', function (): void {
    $container = new Container();

    $container->bind(SimpleService::class);
    $container->bind(BoundService::class);
    $container->tag([SimpleService::class, BoundService::class], 'services');

    $tagged = $container->tagged('services');

    expect($tagged)->toHaveCount(2)
        ->and($tagged[0])->toBeInstanceOf(SimpleService::class)
        ->and($tagged[1])->toBeInstanceOf(BoundService::class);
});

it('autowires closures with explicit scalar parameters', function (): void {
    $container = new Container();

    $result = $container->call(
        static fn(SimpleService $service, string $name): string => $service::class . ':' . $name,
        ['name' => 'lpwork'],
    );

    expect($result)->toBe(SimpleService::class . ':lpwork');
});

it('autowires controller action arrays through the container', function (): void {
    $container = new Container();

    $result = $container->call([ActionController::class, 'show'], ['id' => '15']);

    expect($result)->toBe(SimpleService::class . ':15');
});

it('passes the container to factory bindings', function (): void {
    $container = new Container();

    $container->instance(SimpleService::class, new SimpleService());
    $container->bind(DependentService::class, static function (Container $container): DependentService {
        $service = $container->make(SimpleService::class);

        if (!$service instanceof SimpleService) {
            throw new LogicException('Expected SimpleService.');
        }

        return new DependentService($service);
    });

    $service = $container->make(DependentService::class);

    expect($service)->toBeInstanceOf(DependentService::class);

    if ($service instanceof DependentService) {
        expect($service->service)->toBe($container->make(SimpleService::class));
    }
});

it('shares singleton factory results while resolving their dependencies through the container', function (): void {
    $container = new Container();
    $service = new SimpleService();

    $container->instance(SimpleService::class, $service);
    $container->singleton(DependentService::class, static function (Container $container): DependentService {
        $service = $container->make(SimpleService::class);

        if (!$service instanceof SimpleService) {
            throw new LogicException('Expected SimpleService.');
        }

        return new DependentService($service);
    });

    $first = $container->make(DependentService::class);
    $second = $container->make(DependentService::class);

    expect($first)->toBe($second);

    if ($first instanceof DependentService) {
        expect($first->service)->toBe($service);
    }
});

it('rejects invalid bindings', function (): void {
    $container = new Container();

    expect(function () use ($container): void {
        $container->bind(ServiceContract::class, 'Tests\\support\\container\\MissingService');
    })
        ->toThrow(InvalidBindingException::class, 'Cannot bind [Tests\support\container\ServiceContract] to [Tests\support\container\MissingService]: concrete class does not exist.')
        ->and(function () use ($container): void {
            $container->bind(ServiceContract::class, ServiceContract::class);
        })
        ->toThrow(InvalidBindingException::class, 'Cannot bind [Tests\support\container\ServiceContract] to [Tests\support\container\ServiceContract]: concrete class is not instantiable.');
});

it('throws when an unbound entry cannot be resolved', function (): void {
    $container = new Container();

    expect(fn(): object => $container->make('Tests\\support\\container\\MissingService'))
        ->toThrow(CannotResolveDependencyException::class, 'Cannot resolve container entry [Tests\support\container\MissingService]: class does not exist.')
        ->and(fn(): object => $container->make(ServiceContract::class))
        ->toThrow(CannotResolveDependencyException::class, 'Cannot resolve container entry [Tests\support\container\ServiceContract]: class is not instantiable.');
});

it('throws when constructor scalar parameters cannot be resolved without factory bindings', function (): void {
    $container = new Container();

    expect(fn(): object => $container->make(ScalarDependencyService::class))
        ->toThrow(CannotResolveDependencyException::class, 'Cannot resolve [Tests\support\container\ScalarDependencyService]: constructor parameter [$name] uses builtin type [string]. Register it with a factory binding.');
});

it('throws when constructor parameters have no type and no default value', function (): void {
    $container = new Container();
    $directory = ProviderTestFiles::createDirectory();
    ProviderTestFiles::createFile(
        'UntypedDependencyService.php',
        <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace TestsGenerated\Container;

            final class UntypedDependencyService
            {
                public function __construct($service) {}
            }
            PHP,
        $directory,
    );
    require_once $directory . '/UntypedDependencyService.php';
    $class = 'TestsGenerated\\Container\\UntypedDependencyService';

    expect(fn(): object => $container->make($class))
        ->toThrow(CannotResolveDependencyException::class, 'Cannot resolve [TestsGenerated\Container\UntypedDependencyService]: constructor parameter [$service] has no type.');
});

it('throws when constructor parameters use unsupported union types', function (): void {
    $container = new Container();
    $directory = ProviderTestFiles::createDirectory();
    ProviderTestFiles::createFile(
        'UnionDependencyService.php',
        <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace TestsGenerated\Container;

            use Tests\support\container\BoundService;
            use Tests\support\container\SimpleService;

            final class UnionDependencyService
            {
                public function __construct(SimpleService|BoundService $service) {}
            }
            PHP,
        $directory,
    );
    require_once $directory . '/UnionDependencyService.php';
    $class = 'TestsGenerated\\Container\\UnionDependencyService';

    expect(fn(): object => $container->make($class))
        ->toThrow(CannotResolveDependencyException::class, 'Cannot resolve [TestsGenerated\Container\UnionDependencyService]: constructor parameter [$service] uses unsupported union or intersection type. Register it with a factory binding.');
});

it('throws when a constructor contract dependency has no binding', function (): void {
    $container = new Container();

    expect(fn(): object => $container->make(ContractDependencyService::class))
        ->toThrow(CannotResolveDependencyException::class, 'Cannot resolve [Tests\support\container\ContractDependencyService]: constructor parameter [$service] expects [Tests\support\container\ServiceContract], but no binding exists.');
});

it('throws when a factory does not return an object', function (): void {
    $container = new Container();

    $container->bind(SimpleService::class, static fn(Container $container): string => 'not an object');

    expect(fn(): object => $container->make(SimpleService::class))
        ->toThrow(CannotResolveDependencyException::class, 'Cannot resolve container entry [Tests\support\container\SimpleService]: factory must return an object.');
});

it('throws when autowiring finds a circular dependency', function (): void {
    $container = new Container();

    expect(fn(): object => $container->make(CircularA::class))
        ->toThrow(CircularDependencyException::class, 'Circular dependency detected while resolving [Tests\support\container\CircularA]: Tests\support\container\CircularA -> Tests\support\container\CircularB -> Tests\support\container\CircularA.');
});
