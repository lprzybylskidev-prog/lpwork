<?php

declare(strict_types=1);

use LPWork\Config\Config;
use LPWork\Config\Contracts\ConfigDefinition;
use LPWork\Foundation\Exceptions\InvalidRuntimeEnvironmentException;
use LPWork\Foundation\RuntimeEnvironment;
use LPWork\Foundation\RuntimeEnvironmentFactory;

beforeEach(function (): void {
    Config::reset();
});

afterEach(function (): void {
    Config::reset();
});

it('detects configured production environments', function (): void {
    $environment = new RuntimeEnvironment('staging', ['production', 'staging']);

    expect($environment->name())->toBe('staging')
        ->and($environment->isProduction())->toBeTrue()
        ->and($environment->productionEnvironments())->toBe(['production', 'staging']);
});

it('rejects empty runtime environment names', function (): void {
    expect(fn(): RuntimeEnvironment => new RuntimeEnvironment('', ['production']))
        ->toThrow(InvalidRuntimeEnvironmentException::class);
});

it('creates runtime environment from configuration', function (): void {
    Config::initDefinitions([
        new class implements ConfigDefinition {
            public function key(): string
            {
                return 'app';
            }

            public function values(): array
            {
                return ['env' => 'staging'];
            }
        },
        new class implements ConfigDefinition {
            public function key(): string
            {
                return 'security';
            }

            public function values(): array
            {
                return ['production_environments' => ['production', 'staging']];
            }
        },
    ]);

    $environment = new RuntimeEnvironmentFactory()->create(
        Config::getString('app.env'),
        Config::getArray('security.production_environments'),
    );

    expect($environment->name())->toBe('staging')
        ->and($environment->isProduction())->toBeTrue();
});
