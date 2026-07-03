<?php

declare(strict_types=1);

use App\Modules\Welcome\WelcomeServiceProvider;
use LPWork\Foundation\Contracts\ServiceProvider;

it('exposes the welcome module service provider', function (): void {
    expect(WelcomeServiceProvider::class)->toImplement(ServiceProvider::class);
});
