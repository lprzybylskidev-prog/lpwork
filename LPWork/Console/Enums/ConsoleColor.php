<?php

declare(strict_types=1);

namespace LPWork\Console\Enums;

/**
 * Enumerates the supported console color values.
 */
enum ConsoleColor: int
{
    case LpworkBlue = 0;
    case Black = 30;
    case Red = 31;
    case Green = 32;
    case Yellow = 33;
    case Blue = 34;
    case Magenta = 35;
    case Cyan = 36;
    case White = 37;
    case Gray = 90;

    /**
     * Performs the foreground code operation.
     */
    public function foregroundCode(): int
    {
        if ($this === self::LpworkBlue) {
            return 94;
        }

        return $this->value;
    }

    /**
     * Performs the background code operation.
     */
    public function backgroundCode(): int
    {
        if ($this === self::LpworkBlue) {
            return 104;
        }

        return $this->value + 10;
    }

    /**
     * @return list<int>
     */
    public function foregroundCodes(): array
    {
        if ($this === self::LpworkBlue) {
            return [38, 2, 66, 136, 206];
        }

        return [$this->foregroundCode()];
    }

    /**
     * @return list<int>
     */
    public function backgroundCodes(): array
    {
        if ($this === self::LpworkBlue) {
            return [48, 2, 66, 136, 206];
        }

        return [$this->backgroundCode()];
    }
}
