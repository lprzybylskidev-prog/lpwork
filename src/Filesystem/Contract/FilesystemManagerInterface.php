<?php
declare(strict_types=1);

namespace LPwork\Filesystem\Contract;

use League\Flysystem\FilesystemOperator;

/**
 * Contract for resolving filesystem disks.
 */
interface FilesystemManagerInterface
{
    /**
     * @param string|null $name
     *
     * @return FilesystemOperator
     */
    public function disk(?string $name = null): FilesystemOperator;
}
