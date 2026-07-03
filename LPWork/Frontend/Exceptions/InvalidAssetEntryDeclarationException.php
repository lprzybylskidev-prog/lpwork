<?php

declare(strict_types=1);

namespace LPWork\Frontend\Exceptions;

use InvalidArgumentException;

/**
 * Reports invalid asset entry declaration exception failures.
 */
final class InvalidAssetEntryDeclarationException extends InvalidArgumentException
{
    /**
     * Performs the invalid name operation.
     */
    public static function invalidName(string $name): self
    {
        return new self("Invalid asset entry name [{$name}]. Use the namespace::entry format, for example welcome::app.");
    }

    /**
     * Performs the invalid source path operation.
     */
    public static function invalidSourcePath(string $name, string $sourcePath): self
    {
        return new self("Invalid source path [{$sourcePath}] for asset entry [{$name}]. Use a project-root-relative path.");
    }

    /**
     * Performs the duplicate operation.
     */
    public static function duplicate(string $name): self
    {
        return new self("Asset entry [{$name}] is already registered.");
    }
}
