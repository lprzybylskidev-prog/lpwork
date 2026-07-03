<?php

declare(strict_types=1);

use Tests\support\testing\Architecture\ArchitectureAssertions;

it('keeps application owned PHP files in modules or explicit application boundaries', function (): void {
    ArchitectureAssertions::assertApplicationUsesModuleFirstShape();
});
