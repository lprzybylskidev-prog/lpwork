<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators\Definitions;

use LPWork\Console\FileCreators\FileCreatorDefinition;

/**
 * Defines the contract for file creator definition group.
 */
interface FileCreatorDefinitionGroup
{
    /**
     * @return list<FileCreatorDefinition>
     */
    public function all(): array;
}
