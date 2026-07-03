<?php

declare(strict_types=1);

namespace LPWork\Console\ProjectTasks;

/**
 * Represents the project task filter framework component.
 */
final readonly class ProjectTaskFilter
{
    /**
     * Creates a new ProjectTaskFilter instance.
     */
    public function __construct(
        public ProjectTaskScope $scope = ProjectTaskScope::All,
        public ?string $module = null,
        public bool $browser = false,
    ) {}

    /**
     * Creates a ProjectTaskFilter instance from from input input.
     */
    public static function fromInput(ProjectTaskScope $scope, ?string $module, bool $browser = false): self
    {
        return new self($scope, $module, $browser);
    }
}
