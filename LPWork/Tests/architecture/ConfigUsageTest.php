<?php

declare(strict_types=1);

use Tests\support\testing\Architecture\ArchitectureAssertions;

it('allows Config only in explicit configuration and diagnostic boundaries', function (): void {
    ArchitectureAssertions::assertNoConfigUsageViolations();
});
