<?php

declare(strict_types=1);

use Tests\support\testing\Session\TestSessionStore;

it('seeds and asserts session values', function (): void {
    TestSessionStore::seeded(['user_id' => 15])
        ->assertHas('user_id', 15)
        ->assertMissing('missing')
        ->put('visited', true)
        ->assertHas('visited', true);
});

it('seeds old input and form errors', function (): void {
    TestSessionStore::seeded([])
        ->withOldInput(['title' => 'Draft'])
        ->withErrors(['title' => 'Required'])
        ->assertOldInput('title', 'Draft')
        ->assertError('title', 'Required');
});
