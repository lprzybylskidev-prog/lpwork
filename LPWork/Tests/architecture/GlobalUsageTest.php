<?php

declare(strict_types=1);

use Tests\support\testing\Architecture\ArchitectureAssertions;

it('allows globals only in explicit framework boundaries', function (): void {
    ArchitectureAssertions::assertNoGlobalUsageViolations();
});
