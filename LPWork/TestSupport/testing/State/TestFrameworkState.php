<?php

declare(strict_types=1);

namespace Tests\support\testing\State;

use LPWork\Config\Config;
use LPWork\Environment\Environment;
use PHPUnit\Framework\Assert;

final readonly class TestFrameworkState
{
    public static function reset(): void
    {
        Environment::reset();
        Config::reset();
    }

    public static function assertEnvironmentValue(string $key, string $value): void
    {
        Assert::assertSame($value, Environment::getString($key), sprintf('Unexpected environment value for [%s].', $key));
    }

    public static function assertConfigValue(string $key, mixed $value): void
    {
        Assert::assertSame($value, Config::get($key), sprintf('Unexpected config value for [%s].', $key));
    }
}
