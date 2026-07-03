<?php

declare(strict_types=1);

namespace Tests\support\testing\Storage;

use LPWork\Storage\Contracts\StorageDriver;
use PHPUnit\Framework\Assert;
use Throwable;

final readonly class StorageDriverContract
{
    public function __construct(
        private StorageDriver $driver,
    ) {}

    public function verifiesCoreStorageBehavior(): void
    {
        Assert::assertFalse($this->driver->exists('contract/missing.txt'));

        $this->driver->put('contract/item.txt', "abc\0def");
        Assert::assertTrue($this->driver->exists('contract/item.txt'));
        Assert::assertSame("abc\0def", $this->driver->get('contract/item.txt'));

        Assert::assertFalse($this->driver->putIfMissing('contract/item.txt', 'replaced'));
        Assert::assertSame("abc\0def", $this->driver->get('contract/item.txt'));
        Assert::assertTrue($this->driver->putIfMissing('contract/new.txt', 'created'));
        Assert::assertSame('created', $this->driver->get('contract/new.txt'));

        $this->driver->append('contract/item.txt', 'ghi');
        Assert::assertSame("abc\0defghi", $this->driver->get('contract/item.txt'));

        $result = $this->driver->withExclusiveLock('contract/item.lock', function (): string {
            $this->driver->append('contract/item.txt', '-locked');

            return 'locked';
        });

        Assert::assertSame('locked', $result);
        Assert::assertSame("abc\0defghi-locked", $this->driver->get('contract/item.txt'));

        $this->driver->delete('contract/new.txt');
        $this->driver->delete('contract/new.txt');
        Assert::assertFalse($this->driver->exists('contract/new.txt'));

        $this->driver->put('contract/nested/first.txt', 'first');
        $this->driver->put('contract/nested/deeper/second.txt', 'second');
        $this->driver->clear('contract/nested');
        Assert::assertFalse($this->driver->exists('contract/nested/first.txt'));
        Assert::assertFalse($this->driver->exists('contract/nested/deeper/second.txt'));
        Assert::assertTrue($this->driver->exists('contract/item.txt'));
    }

    public function verifiesMissingFilesFail(): void
    {
        try {
            $this->driver->get('contract/missing.txt');
        } catch (Throwable) {
            return;
        }

        Assert::fail('Storage drivers must fail when reading a missing file.');
    }
}
