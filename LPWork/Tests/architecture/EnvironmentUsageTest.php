<?php

declare(strict_types=1);

use Tests\support\testing\Architecture\ArchitectureAssertions;

it('allows Environment only in configs and Bootstrap init', function (): void {
    ArchitectureAssertions::assertNoEnvironmentUsageViolations();
});
