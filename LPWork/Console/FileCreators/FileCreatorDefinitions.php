<?php

declare(strict_types=1);

namespace LPWork\Console\FileCreators;

use LPWork\Console\FileCreators\Definitions\ApplicationFileCreatorDefinitions;
use LPWork\Console\FileCreators\Definitions\BroadcastingFileCreatorDefinitions;
use LPWork\Console\FileCreators\Definitions\ConsoleFileCreatorDefinitions;
use LPWork\Console\FileCreators\Definitions\DatabaseFileCreatorDefinitions;
use LPWork\Console\FileCreators\Definitions\EventFileCreatorDefinitions;
use LPWork\Console\FileCreators\Definitions\FileCreatorDefinitionGroup;
use LPWork\Console\FileCreators\Definitions\HttpFileCreatorDefinitions;
use LPWork\Console\FileCreators\Definitions\NotificationFileCreatorDefinitions;
use LPWork\Console\FileCreators\Definitions\ValidationFileCreatorDefinitions;
use LPWork\Console\FileCreators\Definitions\ViewFileCreatorDefinitions;

/**
 * Represents the file creator definitions framework component.
 */
final readonly class FileCreatorDefinitions
{
    /**
     * @return list<FileCreatorDefinition>
     */
    public function all(): array
    {
        $definitions = [];

        foreach ($this->groups() as $group) {
            array_push($definitions, ...$group->all());
        }

        return $definitions;
    }

    /**
     * @return list<FileCreatorDefinitionGroup>
     */
    private function groups(): array
    {
        return [
            new ConsoleFileCreatorDefinitions(),
            new HttpFileCreatorDefinitions(),
            new EventFileCreatorDefinitions(),
            new DatabaseFileCreatorDefinitions(),
            new ValidationFileCreatorDefinitions(),
            new NotificationFileCreatorDefinitions(),
            new BroadcastingFileCreatorDefinitions(),
            new ViewFileCreatorDefinitions(),
            new ApplicationFileCreatorDefinitions(),
        ];
    }
}
