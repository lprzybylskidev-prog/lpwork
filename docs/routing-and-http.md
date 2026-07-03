# Routing And HTTP

Routes describe how HTTP requests enter application code. Route declarations belong to the module that owns the behavior.

## Route Providers

A module route provider declares route definition classes:

```php
final class RoutesProvider extends BaseRoutesProvider
{
    protected function routeDefinitions(): array
    {
        return [
            WebRoutes::class,
        ];
    }
}
```

Route definitions receive the framework router:

```php
final class WebRoutes implements RouteDefinition
{
    public function register(Router $router): void
    {
        $router->get('/', [HomeController::class, 'index'])->name('home');
    }
}
```

Use `php lpwork route:list` to inspect registered application routes.

## Route API

The router supports:

- HTTP verbs: `get`, `head`, `post`, `put`, `patch`, `delete`, `options`.
- `match([...], $path, $action)` for selected methods.
- `any($path, $action)` for all common methods.
- `group([...], fn (Router $router) => ...)` for prefixes, name prefixes, middleware, and API formatting.
- `resource($name, $controller, only: ..., except: ..., parameter: ...)` for conventional resource routes.

Route actions can be controller arrays such as `[PostController::class, 'show']` or closures. Prefer controllers for behavior that should be tested, reused, or dependency-injected.

## Groups And Middleware

Route groups accept:

```php
$router->group([
    'prefix' => '/admin',
    'name' => 'admin.',
    'middleware' => ['signed'],
], function (Router $router): void {
    $router->get('/reports', [ReportController::class, 'index'])->name('reports.index');
});
```

Middleware aliases and groups are configured in `App/Shared/Configs/RoutingConfig.php`. Add aliases there when routes should refer to middleware by name.

Use `api: true` in a route group when the route should be treated as API-oriented by the response and exception formatting flow.

## Controllers

Generate module controllers with:

```bash
php lpwork --module=Blog make:controller PostController
```

Controllers are resolved through the container, so constructor dependencies can be injected:

```php
final readonly class PostController
{
    public function __construct(private PostRepository $posts) {}

    public function show(HttpRequest $request): HttpResponse
    {
        $page = $request->queryInteger('page', 1);

        return HttpResponse::json([
            'posts' => $this->posts->page($page),
        ]);
    }
}
```

Keep controllers focused on request orchestration. Move parsing, persistence, external calls, and complex policy decisions into collaborators.

## Requests

Use `LPWork\Requests\HttpRequest` for request state:

- Query data: `queryValue`, `queryString`, `queryInteger`, `queryBoolean`, `queryOnly`, `queryExcept`.
- Input data: `inputValue`, `string`, `integer`, `boolean`, `array`, `only`, `except`.
- Uploaded files: `file`, `files`.
- Metadata: `method`, `path`, `header`, `body`, `host`, `scheme`, `ip`, `expectsJson`, `isJson`.
- Session: `session()` when session middleware has attached a session.

Do not read PHP superglobals from controllers or module services. Use the request boundary.

## Responses

Controllers should return `LPWork\Responses\HttpResponse` or another response-compatible value expected by the dispatcher.

Common response constructors:

```php
HttpResponse::text('Saved');
HttpResponse::html('<p>Saved</p>');
HttpResponse::json(['ok' => true]);
HttpResponse::redirect('/login');
HttpResponse::created('/posts/123');
HttpResponse::noContent();
HttpResponse::file($path);
HttpResponse::download($path, 'report.csv');
```

Response objects represent status, headers, cookies, and body data. They do not emit themselves; emitters own output side effects.
