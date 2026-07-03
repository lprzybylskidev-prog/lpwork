<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\Requests\HttpRequest;
use LPWork\Validation\Exceptions\FormRequestAlreadyInitializedException;
use LPWork\Validation\Exceptions\FormRequestNotInitializedException;
use LPWork\Validation\Exceptions\InvalidFormRequestException;
use LPWork\Validation\Exceptions\ValidationException;
use Tests\support\routing\InjectedMessage;
use Tests\support\validation\FormRequestTestServices;
use Tests\support\validation\InjectedFormRequest;
use Tests\support\validation\StorePostFormRequest;
use Tests\support\validation\UploadFormRequest;
use Tests\support\validation\ValidationTestFiles;

it('builds initialized form requests with validated input', function (): void {
    $request = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/posts',
            'HTTP_ACCEPT' => 'application/json',
        ],
        input: [
            'title' => 'Hello',
            'meta' => ['published' => 'yes'],
            'ignored' => 'raw-only',
        ],
    );

    $form = FormRequestTestServices::factory()->make(StorePostFormRequest::class, $request);

    expect($form)->toBeInstanceOf(StorePostFormRequest::class)
        ->and($form->request())->toBe($request)
        ->and($form->method())->toBe('POST')
        ->and($form->path())->toBe('/posts')
        ->and($form->header('Accept'))->toBe('application/json')
        ->and($form->input())->toBe($request->input())
        ->and($form->validated())->toBe([
            'title' => 'Hello',
            'meta' => ['published' => 'yes'],
        ])
        ->and($form->validatedValue('meta.published'))->toBe('yes')
        ->and($form->string('title'))->toBe('Hello');
});

it('throws validation exceptions before returning invalid form requests', function (): void {
    $request = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/posts',
        ],
        input: [
            'title' => '',
            'meta' => ['published' => 'maybe'],
        ],
    );

    expect(fn() => FormRequestTestServices::factory()->make(StorePostFormRequest::class, $request))
        ->toThrow(ValidationException::class);
});

it('validates request files through form requests', function (): void {
    $avatar = ValidationTestFiles::image();

    try {
        $request = HttpRequest::fromArrays(
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/upload'],
            input: ['title' => 'Avatar'],
            files: [
                'avatar' => [
                    'tmp_name' => $avatar,
                    'name' => 'avatar.png',
                    'type' => 'image/png',
                    'size' => filesize($avatar),
                    'error' => UPLOAD_ERR_OK,
                ],
            ],
        );

        $form = FormRequestTestServices::factory()->make(UploadFormRequest::class, $request);

        expect($form)->toBeInstanceOf(UploadFormRequest::class)
            ->and($form->validated())->toHaveKey('avatar')
            ->and($form->validatedValue('title'))->toBe('Avatar');
    } finally {
        ValidationTestFiles::remove($avatar);
    }
});

it('resolves form request subclasses through the container', function (): void {
    $container = new Container();
    $container->instance(InjectedMessage::class, new InjectedMessage('from-form-request'));

    $request = HttpRequest::fromArrays(
        server: [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/posts',
        ],
        input: [
            'title' => 'Hello',
            'meta' => ['published' => true],
        ],
    );

    $form = FormRequestTestServices::factory($container)->make(InjectedFormRequest::class, $request);

    expect($form)->toBeInstanceOf(InjectedFormRequest::class);

    if ($form instanceof InjectedFormRequest) {
        expect($form->message()->value())->toBe('from-form-request');
    }
});

it('keeps form requests focused on validation without an authorization hook', function (): void {
    expect(method_exists(StorePostFormRequest::class, 'authorize'))->toBeFalse();
});

it('throws when the container resolves a non form request object', function (): void {
    $container = new Container();
    $container->instance(StorePostFormRequest::class, new \stdClass());
    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/posts',
    ]);

    expect(fn() => FormRequestTestServices::factory($container)->make(StorePostFormRequest::class, $request))
        ->toThrow(InvalidFormRequestException::class, 'must resolve to a FormRequest instance');
});

it('guards the form request lifecycle explicitly', function (): void {
    $form = new StorePostFormRequest();
    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/posts',
    ]);

    expect(fn() => $form->request())
        ->toThrow(FormRequestNotInitializedException::class, 'has not been initialized');

    $form->initializeRequest($request);

    expect(fn() => $form->initializeRequest($request))
        ->toThrow(FormRequestAlreadyInitializedException::class, 'has already been initialized');
});
