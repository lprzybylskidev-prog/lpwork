<?php

declare(strict_types=1);

namespace Tests\support\testing\Security;

use LPWork\Security\ApplicationKey;
use PHPUnit\Framework\Assert;

final readonly class TestApplicationKeys
{
    public static function string(string $seed = 'test'): string
    {
        return 'base64:' . base64_encode(str_pad($seed, 32, $seed));
    }

    public static function key(string $seed = 'test'): ApplicationKey
    {
        return ApplicationKey::fromString(self::string($seed));
    }

    public static function assertValid(string $key): void
    {
        Assert::assertInstanceOf(ApplicationKey::class, ApplicationKey::fromString($key));
    }
}
