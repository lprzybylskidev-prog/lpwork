<?php

declare(strict_types=1);

use LPWork\Http\Exceptions\InvalidSpoofedMethodException;
use LPWork\Http\MethodSpoofing;

it('generates hidden input for RESTful method spoofing', function (): void {
    expect(MethodSpoofing::input('put'))
        ->toBe('<input type="hidden" name="_method" value="PUT">')
        ->and(MethodSpoofing::input(' PATCH '))
        ->toBe('<input type="hidden" name="_method" value="PATCH">')
        ->and(MethodSpoofing::input('DELETE'))
        ->toBe('<input type="hidden" name="_method" value="DELETE">');
});

it('throws when method cannot be spoofed', function (): void {
    expect(fn() => MethodSpoofing::input('POST'))
        ->toThrow(InvalidSpoofedMethodException::class);
});

it('resolves spoofed methods from post input', function (): void {
    expect(MethodSpoofing::resolve('POST', ['_method' => 'patch']))->toBe('PATCH')
        ->and(MethodSpoofing::resolve('GET', ['_method' => 'DELETE']))->toBe('GET')
        ->and(MethodSpoofing::resolve('POST', []))->toBe('POST');
});
