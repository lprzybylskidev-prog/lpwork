<?php

declare(strict_types=1);

namespace LPWork\Session;

/**
 * Represents the flash data framework component.
 */
final class FlashData
{
    /**
     * @param list<string> $new
     * @param list<string> $old
     */
    private function __construct(
        private array $new,
        private array $old,
    ) {}

    public static function empty(): self
    {
        return new self([], []);
    }

    /**
     * Creates a FlashData instance from from array input.
     */
    public static function fromArray(mixed $data): self
    {
        if (!is_array($data)) {
            return self::empty();
        }

        return new self(
            self::stringList($data['new'] ?? []),
            self::stringList($data['old'] ?? []),
        );
    }

    /**
     * @return array{new: list<string>, old: list<string>}
     */
    public function toArray(): array
    {
        return [
            'new' => $this->new,
            'old' => $this->old,
        ];
    }

    /**
     * @return list<string>
     */
    public function oldKeys(): array
    {
        return $this->old;
    }

    /**
     * Performs the flash operation.
     */
    public function flash(string $key): void
    {
        $this->pushNew($key);
        $this->removeOld($key);
    }

    /**
     * Performs the keep operation.
     */
    public function keep(string $key): void
    {
        $this->pushNew($key);
        $this->removeOld($key);
    }

    /**
     * Performs the reflash operation.
     */
    public function reflash(): void
    {
        foreach ($this->old as $key) {
            $this->pushNew($key);
        }

        $this->old = [];
    }

    /**
     * Removes a value from this component's backing store.
     */
    public function forget(string $key): void
    {
        $this->new = self::without($this->new, $key);
        $this->old = self::without($this->old, $key);
    }

    /**
     * Performs the age operation.
     */
    public function age(): void
    {
        $this->old = $this->new;
        $this->new = [];
    }

    private function pushNew(string $key): void
    {
        if (!in_array($key, $this->new, true)) {
            $this->new[] = $key;
        }
    }

    private function removeOld(string $key): void
    {
        $this->old = self::without($this->old, $key);
    }

    /**
     * @param list<string> $keys
     *
     * @return list<string>
     */
    private static function without(array $keys, string $key): array
    {
        return array_values(array_filter(
            $keys,
            static fn(string $flashKey): bool => $flashKey !== $key,
        ));
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $strings = [];

        foreach ($value as $item) {
            if (is_string($item)) {
                $strings[] = $item;
            }
        }

        return array_values(array_unique($strings));
    }
}
