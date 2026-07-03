<?php

declare(strict_types=1);

use LPWork\Validation\Exceptions\InvalidValidationRuleDeclarationException;
use LPWork\Validation\Exceptions\ValidationException;
use LPWork\Validation\Exceptions\ValidationRuleNotFoundException;
use LPWork\Validation\ValidationErrorBag;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleRegistry;
use LPWork\Validation\Validator;
use Tests\support\validation\AlwaysFailsRule;
use Tests\support\validation\RequiredRule;

it('stores validation messages and errors as structured translation-ready data', function (): void {
    $message = new ValidationMessage('validation.required', ['field' => 'email']);
    $bag = new ValidationErrorBag();

    $bag->add('email', $message);

    expect($bag->isEmpty())->toBeFalse()
        ->and($bag->has('email'))->toBeTrue()
        ->and($bag->get('email'))->toHaveCount(1)
        ->and($bag->get('email')[0]->field())->toBe('email')
        ->and($bag->get('email')[0]->message())->toBe($message)
        ->and($bag->toArray())->toBe([
            'email' => [
                [
                    'field' => 'email',
                    'message' => [
                        'key' => 'validation.required',
                        'parameters' => ['field' => 'email'],
                    ],
                ],
            ],
        ]);
});

it('registers and returns validation rules by name', function (): void {
    $registry = new ValidationRuleRegistry();
    $rule = new RequiredRule();

    $registry->register($rule);

    expect($registry->has('required'))->toBeTrue()
        ->and($registry->get('required'))->toBe($rule);
});

it('throws when a named validation rule is missing', function (): void {
    expect(fn() => new ValidationRuleRegistry()->get('missing'))
        ->toThrow(ValidationRuleNotFoundException::class, 'Validation rule [missing] is not registered.');
});

it('returns passing validation results without throwing', function (): void {
    $registry = new ValidationRuleRegistry();
    $registry->register(new RequiredRule());

    $result = new Validator($registry)->validate([
        'email' => 'hello@example.test',
        'ignored' => 'not-validated',
    ], [
        'email' => 'required',
    ]);

    expect($result->passes())->toBeTrue()
        ->and($result->fails())->toBeFalse()
        ->and($result->validated())->toBe(['email' => 'hello@example.test'])
        ->and($result->throw())->toBe($result);
});

it('collects validation errors from named and inline rules without throwing directly', function (): void {
    $registry = new ValidationRuleRegistry();
    $registry->register(new RequiredRule());
    $registry->register(new AlwaysFailsRule('after'));

    $result = new Validator($registry)->validate([
        'profile' => ['name' => ''],
    ], [
        'profile.name' => ['required', 'after:alpha,beta', new AlwaysFailsRule('inline', 'validation.inline')],
    ]);

    expect($result->fails())->toBeTrue()
        ->and($result->validated())->toBe([])
        ->and($result->errors()->get('profile.name'))->toHaveCount(3)
        ->and($result->errors()->get('profile.name')[0]->message()->key())->toBe('validation.required')
        ->and($result->errors()->get('profile.name')[1]->message()->parameters()['parameters'])->toBe([
            0 => 'alpha',
            1 => 'beta',
        ])
        ->and($result->errors()->get('profile.name')[2]->message()->key())->toBe('validation.inline');
});

it('throws validation exceptions from failed results with the same error bag', function (): void {
    $registry = new ValidationRuleRegistry();
    $registry->register(new RequiredRule());

    $result = new Validator($registry)->validate([], [
        'email' => 'required',
    ]);

    try {
        $result->throw();
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe($result->errors())
            ->and($exception->getMessage())->toBe('Validation failed.');

        return;
    }

    throw new RuntimeException('ValidationException was not thrown.');
});

it('rejects unsupported rule declarations explicitly', function (): void {
    $registry = new ValidationRuleRegistry();
    $registry->register(new RequiredRule());

    expect(fn() => new Validator($registry)->validate([], [
        'email' => ['required', false],
    ]))->toThrow(InvalidValidationRuleDeclarationException::class, 'must contain rule names or ValidationRule instances');
});

it('rejects empty string rule names explicitly', function (): void {
    $registry = new ValidationRuleRegistry();
    $registry->register(new RequiredRule());

    expect(fn() => new Validator($registry)->validate([], [
        'email' => 'required||email',
    ]))->toThrow(InvalidValidationRuleDeclarationException::class, 'contains an empty rule name');
});
