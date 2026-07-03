<?php

declare(strict_types=1);

use LPWork\Middleware\Contracts\Middleware;
use LPWork\Middleware\MiddlewarePipeline;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;

it('passes request to the destination when pipeline is empty', function (): void {
    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/empty',
    ]);

    $response = new MiddlewarePipeline()->handle(
        $request,
        static fn(HttpRequest $request): HttpResponse => HttpResponse::text($request->path()),
    );

    expect($response->body())->toBe('/empty');
});

it('executes middleware around the destination in order', function (): void {
    $events = [];
    $record = static function (string $event) use (&$events): void {
        $events[] = $event;
    };

    $first = new class ('first', $record) implements Middleware {
        public function __construct(
            private string $name,
            private \Closure $record,
        ) {}

        public function handle(HttpRequest $request, \Closure $next): HttpResponse
        {
            ($this->record)($this->name . '-before');

            $response = $next($request);

            ($this->record)($this->name . '-after');

            return new HttpResponse($response->body() . '|' . $this->name, $response->statusCode(), $response->headers());
        }
    };

    $second = new class ('second', $record) implements Middleware {
        public function __construct(
            private string $name,
            private \Closure $record,
        ) {}

        public function handle(HttpRequest $request, \Closure $next): HttpResponse
        {
            ($this->record)($this->name . '-before');

            $response = $next($request);

            ($this->record)($this->name . '-after');

            return new HttpResponse($response->body() . '|' . $this->name, $response->statusCode(), $response->headers());
        }
    };

    $request = HttpRequest::fromArrays([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/pipeline',
    ]);

    $response = new MiddlewarePipeline([$first, $second])->handle(
        $request,
        function (HttpRequest $request) use (&$events): HttpResponse {
            $events[] = 'destination';

            return HttpResponse::text($request->path());
        },
    );

    expect($events)->toBe([
        'first-before',
        'second-before',
        'destination',
        'second-after',
        'first-after',
    ])->and($response->body())->toBe('/pipeline|second|first');
});
