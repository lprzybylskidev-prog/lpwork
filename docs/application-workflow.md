# Application Workflow

LPWork applications are built around explicit modules under `App/Modules/*`. A module owns the application behavior it exposes and registers its loadable pieces through providers.

Use this workflow when adding application behavior:

1. Choose the owning module or create one with `php lpwork make:module <Name>`.
2. Inspect the module service provider and focused providers before editing.
3. Add controllers, requests, views, assets, translations, commands, migrations, events, listeners, and tests inside the owning module when the behavior is module-specific.
4. Register every loadable piece explicitly through the appropriate provider or config boundary.
5. Verify with focused module tests, then broaden when shared application behavior changed.

## Core Guides

- [Modules](modules.md) explains module ownership, provider structure, generation, and explicit registration.
- [Routing And HTTP](routing-and-http.md) explains route declarations, controllers, requests, responses, middleware, and route inspection.
- [Views And Assets](views-and-assets.md) explains namespaced PHP views, framework assets, module frontend entrypoints, and Vite commands.
- [Validation](validation.md) explains validators, form requests, rule syntax, custom rules, and validation failures.
- [Testing](testing.md) explains backend/frontend test locations, command filters, and verification workflow.

## Good LPWork Application Code

- Keep orchestration separate from parsing, validation, dependency resolution, dispatch, persistence, and side effects once behavior is more than a trivial call.
- Keep providers focused on registration. Move construction variants, parsing, validation, and side-effect work into focused collaborators.
- Use explicit provider or configuration declarations instead of scanning application folders.
- Use framework boundaries for requests, responses, config, sessions, storage, cache, queues, mail, notifications, events, and emitters.
- Prefer application integrations through `App`, Composer/npm packages, providers, and config before changing `LPWork`.

## Useful Commands

| Command | Use |
| --- | --- |
| `php lpwork make:module Blog` | Create and register a module skeleton. |
| `php lpwork make:module Blog --no-frontend` | Create a backend-only module. |
| `php lpwork --module=Blog make:controller HomeController` | Create a controller in a module. |
| `php lpwork route:list` | Inspect registered application routes. |
| `php lpwork frontend:entries` | Inspect declared Vite frontend entrypoints. |
| `php lpwork test --module=Blog` | Run tests for one module. |
| `php lpwork test --backend` | Run backend application tests only. |
| `php lpwork test --frontend` | Run frontend application tests only. |
| `php lpwork check` | Run static analysis and frontend quality checks. |
