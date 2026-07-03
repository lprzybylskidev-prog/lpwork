<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\Foundation\Contracts\ServiceProvider;
use LPWork\Validation\Context\ValidationDebugContextProvider;
use LPWork\Validation\Exceptions\ValidationException;
use LPWork\Validation\FormRequestFactory;
use LPWork\Validation\Providers\ValidationServiceProvider;
use LPWork\Validation\Rules\RequiredRule;
use LPWork\Validation\ValidationErrorBag;
use LPWork\Validation\ValidationMessage;
use LPWork\Validation\ValidationRuleRegistry;
use LPWork\Validation\Validator;

it('defines the validation service provider', function (): void {
    expect(new ValidationServiceProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('registers the validator and built-in rule registry as singletons', function (): void {
    $container = new Container();

    new ValidationServiceProvider()->register($container);

    $registry = $container->make(ValidationRuleRegistry::class);
    $validator = $container->make(Validator::class);
    $formRequests = $container->make(FormRequestFactory::class);

    expect($registry)
        ->toBeInstanceOf(ValidationRuleRegistry::class)
        ->toBe($container->make(ValidationRuleRegistry::class))
        ->and($validator)
        ->toBeInstanceOf(Validator::class)
        ->toBe($container->make(Validator::class))
        ->and($formRequests)
        ->toBeInstanceOf(FormRequestFactory::class)
        ->toBe($container->make(FormRequestFactory::class));

    if ($registry instanceof ValidationRuleRegistry) {
        expect($registry->get('required'))->toBeInstanceOf(RequiredRule::class);
    }
});

it('exposes validation exception errors to debug context', function (): void {
    $bag = new ValidationErrorBag();
    $bag->add('email', new ValidationMessage('validation.email'));
    $context = new HttpDebugContext();
    $context->setThrowable(new ValidationException($bag));

    expect(new ValidationDebugContextProvider()->context($context))->toBe([
        'Validation' => [
            'Errors' => [
                'email' => [
                    [
                        'field' => 'email',
                        'message' => [
                            'key' => 'validation.email',
                            'parameters' => [],
                        ],
                    ],
                ],
            ],
        ],
    ]);
});
