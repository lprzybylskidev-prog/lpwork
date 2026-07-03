<?php

declare(strict_types=1);

namespace Tests\support\routing;

use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;

final readonly class ContainerController
{
    public function __construct(
        private InjectedMessage $message,
    ) {}

    public function index(HttpRequest $request): HttpResponse
    {
        return HttpResponse::text(sprintf('%s %s %s', $request->method(), $request->path(), $this->message->value()));
    }

    public function show(HttpRequest $request, InjectedMessage $message, string $id): HttpResponse
    {
        return HttpResponse::text(sprintf('%s %s %s %s', $request->method(), $request->path(), $id, $message->value()));
    }
}
