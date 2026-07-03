<?php

declare(strict_types=1);

namespace Tests\support\testing\Http;

use LPWork\Http\Cookie;
use LPWork\Http\ViewRenderer;
use LPWork\Requests\HttpRequest;
use LPWork\Responses\HttpResponse;
use RuntimeException;

final class ApplicationHttpIntegrationController
{
    public function index(HttpRequest $request): HttpResponse
    {
        return HttpResponse::text($request->method() . ' ' . $request->path());
    }

    public function redirectWithCookieAndSession(HttpRequest $request): HttpResponse
    {
        $request->session()->put('dashboard_message', 'from-session');

        return HttpResponse::redirect('/dashboard')
            ->withCookie(new Cookie('visited', 'yes'));
    }

    public function dashboard(HttpRequest $request): HttpResponse
    {
        return HttpResponse::json([
            'visited' => $request->cookie('visited', 'missing'),
            'message' => $request->session()->get('dashboard_message', 'missing'),
        ]);
    }

    public function storeJson(HttpRequest $request): HttpResponse
    {
        return HttpResponse::json([
            'title' => $request->inputValue('title'),
            'nested' => $request->inputValue('nested'),
        ], statusCode: 201);
    }

    public function csrfForm(HttpRequest $request): HttpResponse
    {
        $token = $request->session()->get('_csrf_token');

        return HttpResponse::text(is_string($token) ? $token : '');
    }

    public function csrfSubmit(HttpRequest $request): HttpResponse
    {
        $request->session()->put('csrf_result', $request->inputValue('name'));

        return HttpResponse::redirect('/csrf-form');
    }

    public function view(ViewRenderer $views): HttpResponse
    {
        return $views->render('integration.page', ['name' => 'Ada']);
    }

    public function fail(): HttpResponse
    {
        throw new RuntimeException('Application HTTP integration failure.');
    }
}
