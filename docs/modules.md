# Modules

LPWork applications are organized around `App/Modules/*`. The generated layout is a default, not a framework requirement, but all loadable pieces must remain explicitly registered.

## Generated Structure

`php lpwork make:module Blog` creates a module similar to:

```text
App/Modules/Blog/
├── Assets/AssetsProvider.php
├── Broadcasting/BroadcastingProvider.php
├── Configs/ConfigsProvider.php
├── Configs/BlogConfig.php
├── Controllers/HomeController.php
├── Routes/RoutesProvider.php
├── Routes/WebRoutes.php
├── Translation/TranslationProvider.php
├── View/ViewProvider.php
├── BlogServiceProvider.php
├── lang/en_US.json
├── lang/pl_PL.json
├── resources/frontend/app.css
├── resources/frontend/app.ts
├── resources/views/home.php
├── tests/backend/BlogModuleTest.php
└── tests/frontend/app.test.ts
```

Use `php lpwork make:module Blog --no-frontend` when the module should not receive frontend entrypoint files, asset registration, or frontend tests.

Use `--no-register` only when you intentionally want to wire the module manually later.

## Provider Graph

The root application provider registers module providers:

```php
final class AppServiceProvider extends ProviderServiceProvider
{
    protected function serviceProviders(): array
    {
        return [
            WelcomeServiceProvider::class,
        ];
    }
}
```

A module provider coordinates focused providers:

```php
final class WelcomeServiceProvider extends ProviderServiceProvider
{
    protected function serviceProviders(): array
    {
        return [
            AssetsProvider::class,
            ConfigsProvider::class,
            RoutesProvider::class,
            TranslationProvider::class,
            ViewProvider::class,
        ];
    }
}
```

Provider registration should stay cheap and explicit. Do not open network connections, run commands, mutate storage, or do request-time work in providers.

## What Belongs In A Module

Put behavior in a module when it is owned by that feature area:

- HTTP routes and controllers.
- Form requests and other request-specific input objects.
- PHP views, frontend entrypoints, CSS, TypeScript, and translations.
- Commands, migrations, events, listeners, mail/notification classes, and queues owned by that module.
- Backend and frontend tests for module behavior.

Use `App/Shared` only when more than one module needs the same application-level concern. Change `LPWork` only when the user asks for framework behavior.

## Explicit Registration

LPWork does not treat files under a module as automatically loadable. Register them through the owning provider:

- Routes through `RoutesProvider`.
- Views through `ViewProvider`.
- Frontend entries through `AssetsProvider`.
- Translations through `TranslationProvider`.
- Config definitions through a module config provider.
- Broadcasting channels, commands, migrations, events, and listeners through their focused provider boundaries when used.

When adding a new loadable category, first check whether the module already has a focused provider for that category. If not, create the smallest provider that matches the existing framework pattern.

## Nested Or Custom Modules

Nested module names such as `Admin/Reports` are supported by the module generator. A module may use a custom internal layout when registration remains explicit and tests make the behavior easy to verify.

Do not rely on folder shape alone. If a file should be loaded by the framework, register it.
