<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators\Exceptions;

use RuntimeException;

/**
 * Reports file creator exception failures.
 */
final class FileCreatorException extends RuntimeException
{
    /**
     * Reports whether missing name.
     */
    public static function missingName(string $type): self
    {
        return new self("Missing {$type} name.");
    }

    /**
     * Performs the invalid name operation.
     */
    public static function invalidName(string $name): self
    {
        return new self("Invalid class name [{$name}]. Use a PHP class name or nested class path.");
    }

    /**
     * Reports whether cannot infer namespace.
     */
    public static function cannotInferNamespace(string $path): self
    {
        return new self("Cannot infer a namespace for [{$path}]. Pass --namespace when using a custom path outside App or LPWork.");
    }

    /**
     * Performs the already exists operation.
     */
    public static function alreadyExists(string $path): self
    {
        return new self("File already exists: {$path}");
    }

    /**
     * Performs the registration not supported operation.
     */
    public static function registrationNotSupported(string $type): self
    {
        return new self("make:{$type} does not support --register.");
    }

    /**
     * Performs the module missing operation.
     */
    public static function moduleMissing(string $path): self
    {
        return new self("Module does not exist: {$path}");
    }

    /**
     * Performs the module target not supported operation.
     */
    public static function moduleTargetNotSupported(string $target): self
    {
        return new self("Module target is not supported for [{$target}].");
    }

    /**
     * Performs the module cannot use custom path operation.
     */
    public static function moduleCannotUseCustomPath(): self
    {
        return new self('The --module option cannot be combined with --path or --namespace.');
    }

    /**
     * Performs the module or path required operation.
     */
    public static function moduleOrPathRequired(string $type): self
    {
        return new self("make:{$type} requires --module or an explicit --path.");
    }

    /**
     * Performs the provider missing operation.
     */
    public static function providerMissing(string $path): self
    {
        return new self("Provider file does not exist: {$path}");
    }

    /**
     * Reports whether cannot update provider.
     */
    public static function cannotUpdateProvider(string $path, string $method): self
    {
        return new self("Could not update [{$path}]. The [{$method}] method shape is not supported.");
    }
}
