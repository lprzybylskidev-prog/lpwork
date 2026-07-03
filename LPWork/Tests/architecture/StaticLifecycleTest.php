<?php

declare(strict_types=1);

use Tests\support\testing\Architecture\ArchitectureAssertions;

it('requires static framework state to expose an explicit reset lifecycle', function (): void {
    ArchitectureAssertions::assertStaticStateIsResettable();
});
