# Views And Assets

LPWork uses backend-rendered PHP views with explicit view namespaces and Vite-managed frontend entrypoints.

## View Namespaces

Register module view namespaces through a module view provider:

```php
final class ViewProvider extends PhpViewEngineProvider
{
    protected function viewNamespaces(): array
    {
        return [
            'welcome' => 'App/Modules/Welcome/resources/views',
        ];
    }
}
```

Render namespaced views from controllers:

```php
final readonly class HomeController
{
    public function index(ViewRenderer $views): HttpResponse
    {
        return $views->render('welcome::home', [
            'title' => 'Welcome',
        ]);
    }
}
```

The `view` config also declares application-level view paths under `resources/views`.

## PHP Views

PHP view files receive explicit data and framework view helpers through the view context:

```php
<?php

declare(strict_types=1);

use LPWork\View\Contracts\ViewContext;

/** @var ViewContext $view */
/** @var string $title */

?>
<h1><?= $view->e($title) ?></h1>
```

Use `$view->e(...)` for escaping and `$view->t(...)` for translations. Do not echo untrusted data directly.

## Frontend Entrypoints

Register frontend entrypoints through `AssetEntrypointsProvider`:

```php
final class AssetsProvider extends AssetEntrypointsProvider
{
    protected function assetEntries(): array
    {
        return [
            'welcome::app' => 'App/Modules/Welcome/resources/frontend/app.ts',
        ];
    }
}
```

Entry names use `module::entry`. Source paths are project-root-relative and should not be absolute or traverse parent directories.

Render a declared entry from a PHP view:

```php
<?= $assets->entry('welcome::app') ?>
```

The renderer uses the Vite dev server during frontend development and the build manifest after `php lpwork frontend:build`.

## Frontend Commands

| Command | Use |
| --- | --- |
| `php lpwork frontend:entries` | Show declared Vite entrypoints. |
| `php lpwork frontend:dev` | Start the Vite development server. |
| `php lpwork frontend:build` | Build frontend assets and manifest. |
| `php lpwork frontend:check` | Run TypeScript, ESLint, Stylelint, and Prettier checks. |
| `php lpwork frontend:format` | Format frontend files. |
| `php lpwork frontend:test` | Run frontend unit tests. |

Application frontend tests live under `App/Modules/*/tests/frontend`.

## When UI Changes

When changing rendered views, CSS, or interactive frontend behavior:

- Keep the view or asset inside the module that owns it.
- Update translations when text changes.
- Run frontend checks or tests for changed frontend assets.
- Use browser checks for layout, interaction, or asset-loading behavior that cannot be trusted from static tests alone.
