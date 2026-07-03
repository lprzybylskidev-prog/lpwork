<?php

declare(strict_types=1);

use LPWork\Http\Contracts\HttpException;
use LPWork\Http\Exceptions\BadRequestException;
use LPWork\Http\Exceptions\ConflictException;
use LPWork\Http\Exceptions\ForbiddenException;
use LPWork\Http\Exceptions\GoneException;
use LPWork\Http\Exceptions\MethodNotAllowedException;
use LPWork\Http\Exceptions\NotFoundException;
use LPWork\Http\Exceptions\PayloadTooLargeException;
use LPWork\Http\Exceptions\ServiceUnavailableException;
use LPWork\Http\Exceptions\TooManyRequestsException;
use LPWork\Http\Exceptions\UnauthorizedException;
use LPWork\Http\Exceptions\UnprocessableEntityException;

it('maps Http exceptions to status codes', function (HttpException $exception, int $statusCode): void {
    expect($exception->statusCode())->toBe($statusCode)
        ->and($exception->getMessage())->toBe((string) $statusCode);
})->with([
    [new BadRequestException(), 400],
    [new UnauthorizedException(), 401],
    [new ForbiddenException(), 403],
    [new NotFoundException(), 404],
    [new MethodNotAllowedException(['GET']), 405],
    [new ConflictException(), 409],
    [new GoneException(), 410],
    [new PayloadTooLargeException(), 413],
    [new UnprocessableEntityException(), 422],
    [new TooManyRequestsException(), 429],
    [new ServiceUnavailableException(), 503],
]);

it('keeps Http exception messages and headers', function (): void {
    expect(new NotFoundException('Article not found')->getMessage())->toBe('Article not found');
    expect(new MethodNotAllowedException(['GET', 'POST'])->headers())->toBe(['Allow' => 'GET, POST']);
    expect(UnauthorizedException::withAuthenticateHeader('Bearer')->headers())->toBe(['WWW-Authenticate' => 'Bearer']);
    expect(TooManyRequestsException::withRetryAfter('60')->headers())->toBe(['Retry-After' => '60']);
    expect(ServiceUnavailableException::withRetryAfter('120')->headers())->toBe(['Retry-After' => '120']);
});
