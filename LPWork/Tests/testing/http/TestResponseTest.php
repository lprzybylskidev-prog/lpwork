<?php

declare(strict_types=1);

use LPWork\Http\Cookie;
use LPWork\Responses\HttpResponse;
use LPWork\Session\Session;
use PHPUnit\Framework\AssertionFailedError;
use Tests\support\testing\Http\TestResponse;

it('asserts status redirects headers cookies and content', function (): void {
    $response = new TestResponse(
        HttpResponse::redirect('/login')
            ->withHeader('X-Test', 'yes')
            ->withCookie(new Cookie('theme', 'dark')),
    );

    $response
        ->assertStatus(302)
        ->assertRedirect('/login')
        ->assertHeader('X-Test', 'yes')
        ->assertHeaderMissing('X-Missing')
        ->assertCookie('theme', 'dark')
        ->assertCookieMissing('missing')
        ->assertEmptyBody();
});

it('asserts common status and body helpers', function (): void {
    new TestResponse(HttpResponse::text('Hello framework'))
        ->assertOk()
        ->assertSee('framework')
        ->assertDontSee('missing')
        ->assertBody('Hello framework');

    new TestResponse(HttpResponse::created('/articles/1'))->assertCreated();
    new TestResponse(HttpResponse::noContent())->assertNoContent();
    new TestResponse(HttpResponse::text('missing', 404))->assertNotFound();
});

it('asserts expired cookies', function (): void {
    new TestResponse(HttpResponse::text('')->withoutCookie('session'))
        ->assertCookieExpired('session');
});

it('asserts JSON response bodies', function (): void {
    new TestResponse(HttpResponse::json([
        'article' => [
            'title' => 'Draft',
            'published' => false,
        ],
        'tags' => ['php', 'testing'],
    ]))
        ->assertValidJson()
        ->assertExactJson([
            'article' => [
                'title' => 'Draft',
                'published' => false,
            ],
            'tags' => ['php', 'testing'],
        ])
        ->assertJsonFragment(['title' => 'Draft'])
        ->assertJsonFragment(['article' => ['published' => false]])
        ->assertJsonMissingFragment(['title' => 'Published'])
        ->assertJsonPath('article.title', 'Draft')
        ->assertJsonPath('tags.0', 'php');
});

it('reports invalid JSON diagnostics', function (): void {
    expect(fn(): TestResponse => new TestResponse(HttpResponse::text('{invalid'))->assertValidJson())
        ->toThrow(AssertionFailedError::class, 'Response body is not valid JSON: Syntax error');
});

it('asserts attached session state', function (): void {
    $session = new Session(['user_id' => 15]);
    $session->flashInput(['title' => 'Draft']);
    $session->flashErrors(['title' => 'Required']);

    new TestResponse(HttpResponse::redirect('/articles'), $session)
        ->assertSessionHas('user_id', 15)
        ->assertSessionMissing('missing')
        ->assertOldInput('title', 'Draft')
        ->assertSessionError('title', 'Required');
});
