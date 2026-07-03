<?php

declare(strict_types=1);

use LPWork\Requests\UploadedFile;
use LPWork\Validation\Exceptions\InvalidValidationRuleParameterException;
use Tests\support\validation\ValidationTestFiles;
use Tests\support\validation\ValidationTestServices;

it('validates required values', function (): void {
    $validator = ValidationTestServices::validator();

    expect($validator->validate(['name' => 'Ada'], ['name' => 'required'])->passes())->toBeTrue()
        ->and($validator->validate(['name' => ''], ['name' => 'required'])->errors()->get('name')[0]->message()->key())
        ->toBe('validation.required')
        ->and($validator->validate([], ['name' => 'required'])->errors()->get('name')[0]->message()->parameters())
        ->toBe(['field' => 'name']);
});

it('validates scalar and array types', function (): void {
    $validator = ValidationTestServices::validator();

    $result = $validator->validate([
        'name' => 15,
        'age' => '15.5',
        'score' => '10',
        'enabled' => 'yes',
        'tags' => 'php',
    ], [
        'name' => 'string',
        'age' => 'integer',
        'score' => 'numeric',
        'enabled' => 'boolean',
        'tags' => 'array',
    ]);

    expect($result->errors()->get('name')[0]->message()->key())->toBe('validation.string')
        ->and($result->errors()->get('age')[0]->message()->key())->toBe('validation.integer')
        ->and($result->errors()->get('score'))->toBe([])
        ->and($result->errors()->get('enabled'))->toBe([])
        ->and($result->errors()->get('tags')[0]->message()->key())->toBe('validation.array');
});

it('validates email values', function (): void {
    $validator = ValidationTestServices::validator();

    expect($validator->validate(['email' => 'ada@example.test'], ['email' => 'email'])->passes())->toBeTrue()
        ->and($validator->validate(['email' => 'not-an-email'], ['email' => 'email'])->errors()->get('email')[0]->message()->key())
        ->toBe('validation.email');
});

it('validates minimum and maximum sizes for strings arrays and numeric values', function (): void {
    $validator = ValidationTestServices::validator();

    $result = $validator->validate([
        'name' => 'Ada',
        'tags' => ['php', 'validation', 'tests'],
        'age' => 17,
        'score' => '101',
    ], [
        'name' => 'min:4',
        'tags' => 'max:2',
        'age' => 'min:18',
        'score' => 'max:100',
    ]);

    expect($result->errors()->get('name')[0]->message()->key())->toBe('validation.min')
        ->and($result->errors()->get('name')[0]->message()->parameters())->toBe(['field' => 'name', 'min' => 4.0])
        ->and($result->errors()->get('tags')[0]->message()->key())->toBe('validation.max')
        ->and($result->errors()->get('age')[0]->message()->key())->toBe('validation.min')
        ->and($result->errors()->get('score')[0]->message()->parameters())->toBe(['field' => 'score', 'max' => 100.0]);
});

it('supports optional fields and value comparison rules', function (): void {
    $validator = ValidationTestServices::validator();

    $result = $validator->validate([
        'password' => 'secret',
        'password_confirmation' => 'secret',
        'role' => 'admin',
        'status' => 'archived',
        'profile' => [
            'nickname' => '',
        ],
        'same_a' => 'x',
        'same_b' => 'x',
        'different_a' => 'x',
        'different_b' => 'y',
    ], [
        'missing' => 'sometimes|string',
        'profile.nickname' => 'nullable|min:3',
        'password' => 'confirmed',
        'role' => 'in:admin,editor',
        'status' => 'not_in:deleted,banned',
        'same_a' => 'same:same_b',
        'different_a' => 'different:different_b',
        'name' => 'required',
    ]);

    expect($result->errors()->get('missing'))->toBe([])
        ->and($result->errors()->get('profile.nickname'))->toBe([])
        ->and($result->errors()->get('password'))->toBe([])
        ->and($result->errors()->get('role'))->toBe([])
        ->and($result->errors()->get('status'))->toBe([])
        ->and($result->errors()->get('same_a'))->toBe([])
        ->and($result->errors()->get('different_a'))->toBe([])
        ->and($result->errors()->get('name')[0]->message()->key())->toBe('validation.required')
        ->and($result->validated())->toBe([]);
});

it('validates size ranges and exact sizes', function (): void {
    $validator = ValidationTestServices::validator();

    $result = $validator->validate([
        'name' => 'Ada',
        'code' => 'abc',
        'tags' => ['php', 'tests'],
    ], [
        'name' => 'between:2,4',
        'code' => 'size:3',
        'tags' => 'between:1,2',
        'too_short' => 'between:2,4',
    ]);

    expect($result->errors()->get('name'))->toBe([])
        ->and($result->errors()->get('code'))->toBe([])
        ->and($result->errors()->get('tags'))->toBe([])
        ->and($result->errors()->get('too_short')[0]->message()->key())->toBe('validation.between');
});

it('validates string formats', function (): void {
    $validator = ValidationTestServices::validator();

    $result = $validator->validate([
        'alpha' => 'Ada',
        'alpha_num' => 'Ada123',
        'alpha_dash' => 'ada_123-test',
        'lower' => 'ada',
        'upper' => 'ADA',
        'start' => 'lpwork-framework',
        'end' => 'avatar.png',
        'contains' => 'small php framework',
        'regex' => 'ABC-123',
        'url' => 'https://example.test',
        'uuid' => '123e4567-e89b-12d3-a456-426614174000',
        'json' => '{"ok":true}',
        'ip' => '127.0.0.1',
        'ipv4' => '127.0.0.1',
        'ipv6' => '::1',
        'bad' => 'no spaces please',
    ], [
        'alpha' => 'alpha',
        'alpha_num' => 'alpha_num',
        'alpha_dash' => 'alpha_dash',
        'lower' => 'lowercase',
        'upper' => 'uppercase',
        'start' => 'starts_with:lpwork,app',
        'end' => 'ends_with:.png,.jpg',
        'contains' => 'contains:php,laravel',
        'regex' => 'regex:/^[A-Z]+-\d+$/',
        'url' => 'url',
        'uuid' => 'uuid',
        'json' => 'json',
        'ip' => 'ip',
        'ipv4' => 'ipv4',
        'ipv6' => 'ipv6',
        'bad' => 'alpha_dash',
    ]);

    foreach (['alpha', 'alpha_num', 'alpha_dash', 'lower', 'upper', 'start', 'end', 'contains', 'regex', 'url', 'uuid', 'json', 'ip', 'ipv4', 'ipv6'] as $field) {
        expect($result->errors()->get($field))->toBe([]);
    }

    expect($result->errors()->get('bad')[0]->message()->key())->toBe('validation.alpha_dash');
});

it('validates dates and numeric comparisons', function (): void {
    $validator = ValidationTestServices::validator();

    $result = $validator->validate([
        'date' => '2026-06-23',
        'formatted' => '23/06/2026',
        'start' => '2026-06-01',
        'end' => '2026-06-30',
        'age' => 18,
        'score' => '99.5',
        'digits' => '12345',
        'price' => '12.50',
        'bad_decimal' => '12.5',
    ], [
        'date' => 'date',
        'formatted' => 'date_format:d/m/Y',
        'start' => 'before:end',
        'end' => 'after:start',
        'age' => 'gte:18|lte:65',
        'score' => 'gt:90|lt:100',
        'digits' => 'digits:5|digits_between:4,6',
        'price' => 'decimal:2',
        'bad_decimal' => 'decimal:2',
    ]);

    foreach (['date', 'formatted', 'start', 'end', 'age', 'score', 'digits', 'price'] as $field) {
        expect($result->errors()->get($field))->toBe([]);
    }

    expect($result->errors()->get('bad_decimal')[0]->message()->key())->toBe('validation.decimal');
});

it('validates array shape and item counts', function (): void {
    $validator = ValidationTestServices::validator();

    $result = $validator->validate([
        'list' => ['a', 'b'],
        'assoc' => ['name' => 'Ada'],
        'profile' => ['name' => 'Ada', 'email' => 'ada@example.test'],
        'unique' => ['a', 'b', 'c'],
        'counted' => ['a', 'b'],
        'duplicates' => ['a', 'a'],
    ], [
        'list' => 'list|min_items:2|max_items:3',
        'assoc' => 'assoc',
        'profile' => 'required_array_keys:name,email',
        'unique' => 'distinct',
        'counted' => 'count:2',
        'duplicates' => 'distinct',
    ]);

    foreach (['list', 'assoc', 'profile', 'unique', 'counted'] as $field) {
        expect($result->errors()->get($field))->toBe([]);
    }

    expect($result->errors()->get('duplicates')[0]->message()->key())->toBe('validation.distinct');
});

it('validates local files and uploaded file arrays', function (): void {
    $validator = ValidationTestServices::validator();
    $text = ValidationTestFiles::file('document.txt', 'hello');
    $image = ValidationTestFiles::image();

    try {
        $result = $validator->validate([
            'document' => $text,
            'upload' => [
                'tmp_name' => $text,
                'name' => 'document.txt',
                'type' => 'text/plain',
                'size' => 5,
                'error' => UPLOAD_ERR_OK,
            ],
            'avatar' => [
                'tmp_name' => $image,
                'name' => 'avatar.png',
                'type' => 'image/png',
                'size' => filesize($image),
                'error' => UPLOAD_ERR_OK,
            ],
            'missing' => $text . '.missing',
        ], [
            'document' => 'file|extensions:txt|min_file_size:5|max_file_size:5|file_size:5',
            'upload' => 'mimes:text/plain,txt',
            'avatar' => 'image|mimes:image/png,png|dimensions:1,1',
            'missing' => 'file',
        ]);

        expect($result->errors()->get('document'))->toBe([])
            ->and($result->errors()->get('upload'))->toBe([])
            ->and($result->errors()->get('avatar'))->toBe([])
            ->and($result->errors()->get('missing')[0]->message()->key())->toBe('validation.file');
    } finally {
        ValidationTestFiles::remove($text);
        ValidationTestFiles::remove($image);
    }
});

it('validates uploaded file objects using real file metadata', function (): void {
    $validator = ValidationTestServices::validator();
    $text = ValidationTestFiles::file('fake-avatar.png', 'hello');
    $image = ValidationTestFiles::image();

    try {
        $imageSize = filesize($image);

        if ($imageSize === false) {
            throw new RuntimeException('Could not read validation image size.');
        }

        $result = $validator->validate([
            'avatar' => new UploadedFile(
                temporaryPath: $image,
                clientName: 'avatar.png',
                clientMimeType: 'application/octet-stream',
                size: $imageSize,
            ),
            'fake_image' => new UploadedFile(
                temporaryPath: $text,
                clientName: 'fake-avatar.png',
                clientMimeType: 'image/png',
                size: 5,
            ),
            'failed_upload' => new UploadedFile(
                temporaryPath: $image,
                clientName: 'avatar.png',
                clientMimeType: 'image/png',
                size: $imageSize,
                error: UPLOAD_ERR_CANT_WRITE,
            ),
            'missing_upload' => new UploadedFile(
                temporaryPath: $image . '.missing',
                clientName: 'avatar.png',
                clientMimeType: 'image/png',
                size: 100,
            ),
        ], [
            'avatar' => 'file|image|mimes:image/png,png|extensions:png|dimensions:1,1',
            'fake_image' => 'image',
            'failed_upload' => 'file|image|mimes:image/png,png|extensions:png|file_size:' . $imageSize,
            'missing_upload' => 'file|extensions:png|file_size:100',
        ]);

        $errorKeys = $result->errors()->toArray();
        $failedUploadErrors = array_map(
            static fn(array $error): string => $error['message']['key'],
            $errorKeys['failed_upload'] ?? [],
        );
        $missingUploadErrors = array_map(
            static fn(array $error): string => $error['message']['key'],
            $errorKeys['missing_upload'] ?? [],
        );

        expect($result->errors()->get('avatar'))->toBe([])
            ->and($result->errors()->get('fake_image')[0]->message()->key())->toBe('validation.image')
            ->and($failedUploadErrors)->toBe([
                'validation.file',
                'validation.image',
                'validation.mimes',
                'validation.extensions',
                'validation.file_size',
            ])
            ->and($missingUploadErrors)->toBe([
                'validation.file',
                'validation.extensions',
                'validation.file_size',
            ]);
    } finally {
        ValidationTestFiles::remove($text);
        ValidationTestFiles::remove($image);
    }
});

it('throws explicit errors for invalid min and max parameters', function (): void {
    $validator = ValidationTestServices::validator();

    expect(fn() => $validator->validate(['name' => 'Ada'], ['name' => 'min']))
        ->toThrow(InvalidValidationRuleParameterException::class, 'Validation rule [min] requires parameter [min].')
        ->and(fn() => $validator->validate(['name' => 'Ada'], ['name' => 'max:large']))
        ->toThrow(InvalidValidationRuleParameterException::class, 'Validation rule [max] parameter [max] must be numeric.');
});
