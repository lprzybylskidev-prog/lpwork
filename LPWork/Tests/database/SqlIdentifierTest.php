<?php

declare(strict_types=1);

use LPWork\Database\Exceptions\InvalidSqlIdentifierException;
use LPWork\Database\SqlIdentifier;

it('accepts simple SQL table identifiers', function (): void {
    expect(SqlIdentifier::table('cache_entries'))->toBe('cache_entries')
        ->and(SqlIdentifier::table('QueueJobs2026'))->toBe('QueueJobs2026')
        ->and(SqlIdentifier::table('_framework_table'))->toBe('_framework_table');
});

it('rejects unsafe SQL table identifiers', function (string $identifier): void {
    expect(fn(): string => SqlIdentifier::table($identifier))
        ->toThrow(InvalidSqlIdentifierException::class);
})->with([
    '',
    'cache entries',
    '1cache_entries',
    'cache_entries; drop table users',
    'cache.entries',
]);
