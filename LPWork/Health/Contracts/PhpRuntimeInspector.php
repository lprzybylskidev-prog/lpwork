<?php

declare(strict_types=1);

namespace LPWork\Health\Contracts;

/**
 * Defines the contract for php runtime inspector.
 */
interface PhpRuntimeInspector
{
    /**
     * Performs the php version id operation.
     */
    public function phpVersionId(): int;

    /**
     * Performs the php version operation.
     */
    public function phpVersion(): string;

    /**
     * Performs the extension loaded operation.
     */
    public function extensionLoaded(string $extension): bool;

    /**
     * @return list<string>
     */
    public function pdoDrivers(): array;
}
