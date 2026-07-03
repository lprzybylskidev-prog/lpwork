<?php

declare(strict_types=1);

use LPWork\Kernels\Http\ControllerDispatcher;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use LPWork\Routing\Exceptions\ClosureRouteParameterException;
use LPWork\Routing\Exceptions\InvalidRouteResponseException;
use LPWork\Routing\Router;
use LPWork\Validation\Exceptions\ValidationException;
use LPWork\Validation\Providers\ValidationServiceProvider;
use Tests\support\ApplicationFactory;
use Tests\support\routing\ContainerController;
use Tests\support\routing\InjectedMessage;
use Tests\support\routing\TestController;
use Tests\support\validation\StorePostFormRequest;

it('dispatches matched controller actions with route parameters', function (): void {
    $router = new Router();
    $router->get('/posts/{id}', [TestController::class, 'show']);
    $match = $router->routes()->match('GET', '/posts/15');

    $response = new ControllerDispatcher(ApplicationFactory::create())
        ->dispatch(HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/posts/15',
        ]), $match);

    expect($response->body())->toBe('GET /posts/15 15');
});

it('resolves controllers through the application container', function (): void {
    $app = ApplicationFactory::create();
    $app->container()->instance(InjectedMessage::class, new InjectedMessage('from-dispatcher'));

    $router = new Router();
    $router->get('/container-controller', [ContainerController::class, 'index']);
    $match = $router->routes()->match('GET', '/container-controller');

    $response = new ControllerDispatcher($app)
        ->dispatch(HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/container-controller',
        ]), $match);

    expect($response->body())->toBe('GET /container-controller from-dispatcher');
});

it('dispatches controller actions through the container with route parameters', function (): void {
    $app = ApplicationFactory::create();
    $app->container()->instance(InjectedMessage::class, new InjectedMessage('from-method'));

    $router = new Router();
    $router->get('/container-controller/{id}', [ContainerController::class, 'show']);
    $match = $router->routes()->match('GET', '/container-controller/15');

    $response = new ControllerDispatcher($app)
        ->dispatch(HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/container-controller/15',
        ]), $match);

    expect($response->body())->toBe('GET /container-controller/15 15 from-method');
});

it('resolves form request controller action parameters before dispatch', function (): void {
    $app = ApplicationFactory::create();
    $app->register(new ValidationServiceProvider());

    $router = new Router();
    $router->post('/posts/{id}', [TestController::class, 'validatedStore']);
    $match = $router->routes()->match('POST', '/posts/15');

    $response = new ControllerDispatcher($app)
        ->dispatch(HttpRequest::fromArrays(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/posts/15',
            ],
            input: [
                'title' => 'Hello',
                'meta' => ['published' => true],
                'ignored' => 'raw-only',
            ],
        ), $match);

    expect($response->body())->toBe('POST /posts/15 15 Hello');
});

it('throws validation exceptions before controller actions run', function (): void {
    $app = ApplicationFactory::create();
    $app->register(new ValidationServiceProvider());

    $router = new Router();
    $router->post('/posts', [TestController::class, 'invalidFormRequestShouldNotRun']);
    $match = $router->routes()->match('POST', '/posts');

    expect(fn() => new ControllerDispatcher($app)
        ->dispatch(HttpRequest::fromArrays(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/posts',
            ],
            input: ['title' => ''],
        ), $match))
        ->toThrow(ValidationException::class);
});

it('dispatches closure route actions', function (): void {
    $router = new Router();
    $router->get('/closure/{name}', static function (HttpRequest $request, string $name): HttpResponse {
        return HttpResponse::text($request->path() . ' ' . $name);
    });
    $match = $router->routes()->match('GET', '/closure/lpwork');

    $response = new ControllerDispatcher(ApplicationFactory::create())
        ->dispatch(HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/closure/lpwork',
        ]), $match);

    expect($response->body())->toBe('/closure/lpwork lpwork');
});

it('dispatches closure route actions with container dependencies', function (): void {
    $app = ApplicationFactory::create();
    $app->container()->instance(InjectedMessage::class, new InjectedMessage('from-container'));

    $router = new Router();
    $router->get('/closure/{name}', static function (InjectedMessage $message, HttpRequest $request, string $name): HttpResponse {
        return HttpResponse::text($request->method() . ' ' . $name . ' ' . $message->value());
    });
    $match = $router->routes()->match('GET', '/closure/lpwork');

    $response = new ControllerDispatcher($app)
        ->dispatch(HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/closure/lpwork',
        ]), $match);

    expect($response->body())->toBe('GET lpwork from-container');
});

it('resolves form request closure route action parameters', function (): void {
    $app = ApplicationFactory::create();
    $app->register(new ValidationServiceProvider());

    $router = new Router();
    $router->post('/closure', static function (StorePostFormRequest $request): HttpResponse {
        return HttpResponse::text($request->string('title'));
    });
    $match = $router->routes()->match('POST', '/closure');

    $response = new ControllerDispatcher($app)
        ->dispatch(HttpRequest::fromArrays(
            server: [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/closure',
            ],
            input: [
                'title' => 'Hello',
                'meta' => ['published' => true],
            ],
        ), $match);

    expect($response->body())->toBe('Hello');
});

it('throws clear errors for closure route parameters that cannot be resolved', function (): void {
    $router = new Router();
    $router->get('/closure', static function (string $missing): HttpResponse {
        return HttpResponse::text($missing);
    });
    $match = $router->routes()->match('GET', '/closure');

    expect(fn() => new ControllerDispatcher(ApplicationFactory::create())
        ->dispatch(HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/closure',
        ]), $match))
        ->toThrow(ClosureRouteParameterException::class, 'Route closure for [/closure] cannot resolve parameter [$missing].');
});

it('throws clear errors when closure routes return invalid responses', function (): void {
    $router = new Router();
    $router->get('/closure', static fn(): string => 'invalid');
    $match = $router->routes()->match('GET', '/closure');

    expect(fn() => new ControllerDispatcher(ApplicationFactory::create())
        ->dispatch(HttpRequest::fromArrays([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/closure',
        ]), $match))
        ->toThrow(InvalidRouteResponseException::class, 'Route closure for [/closure] must return an HttpResponse.');
});
