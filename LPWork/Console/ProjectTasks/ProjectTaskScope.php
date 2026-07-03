<?php

declare(strict_types=1);

namespace LPWork\Console\ProjectTasks;

/**
 * Enumerates the supported project task scope values.
 */
enum ProjectTaskScope
{
    case All;
    case Backend;
    case Frontend;

    /**
     * Creates a ProjectTaskScope instance from from flags input.
     */
    public static function fromFlags(bool $backend, bool $frontend): self
    {
        if ($backend && !$frontend) {
            return self::Backend;
        }

        if ($frontend && !$backend) {
            return self::Frontend;
        }

        return self::All;
    }

    /**
     * Performs the includes backend operation.
     */
    public function includesBackend(): bool
    {
        return $this !== self::Frontend;
    }

    /**
     * Performs the includes frontend operation.
     */
    public function includesFrontend(): bool
    {
        return $this !== self::Backend;
    }
}
