# LPWork

LPWork is a personal PHP framework learning project built as a recruiter-readable, source-available portfolio snapshot. It demonstrates a complete backend-oriented framework surface without presenting itself as a public package ecosystem.

The repository is visible for CV, portfolio, and code-review purposes. Use, copying, modification, redistribution, and derivative works require explicit written permission; see [LICENSE.md](LICENSE.md).

## Project Status

`v1.0.0` is planned as the first public LPWork snapshot, not the end of development. The framework remains an active learning and architecture project, with future work continuing through explicit roadmap items and immutable release tags.

LPWork is not distributed as a Composer package. Installable application snapshots are intended to come from tagged release archives through the standalone installers once a release archive URL has been configured.

## What LPWork Shows

LPWork focuses on explicit framework boundaries and small, understandable components:

- HTTP routing, requests, responses, middleware, controllers, signed URLs, and backend-rendered PHP views.
- Configuration from environment-backed config definitions with cache support and validation.
- Runtime services for database connections, migrations, cache, sessions, locks, storage, queues, scheduler, mail, notifications, broadcasting, logging, and metrics.
- Security helpers for application keys, HMAC signatures, CSRF protection, upload limits, secure cookies, and security headers.
- Console tooling with command metadata, help output, completion scripts, generators, health checks, route/config inspection, migrations, queue workers, scheduler commands, maintenance mode, formatting, tests, and static analysis.
- Debugging and observability surfaces including health checks, `lpwork about`, safe production error pages, a debug exception renderer, debug dump tools, and an `APP_DEBUG` debugbar.
- Framework and application test utilities for HTTP, CLI, container, cache, session, storage, database, queue, events, mail, notifications, broadcasting, and architecture checks.

The default welcome page and `php lpwork about` use the same framework module catalog, so the visual demo, CLI diagnostics, and this README describe the same capability set.

## Architecture Direction

LPWork favors explicit registration over magic discovery. Application code registers routes, providers, config definitions, views, translations, commands, migrations, assets, events, and other loadable pieces through the boundary that owns them.

Framework internals live under `LPWork`. The default `App` tree is a skeleton, demo, and integration surface that shows how applications compose framework capabilities without making application structure a hidden framework assumption.

Entrypoints stay thin, providers register services and declarations, and runtime side effects are kept behind explicit kernels, emitters, workers, commands, or service boundaries. Tests and static analysis are treated as design feedback rather than after-the-fact checks.

## Repository Layout

- `LPWork/` contains framework modules, framework-owned resources, tests, and test support.
- `App/` contains the default skeleton/demo application used as an integration surface.
- `docs/` contains user-facing LPWork application documentation and the generated application agent guidance template.
- `installers/` contains standalone Unix shell and Windows PowerShell installers, currently blocked until an immutable tagged release archive URL is configured.
- `.devcontainer/` contains the VS Code development workspace with optional local infrastructure for the framework.

## Development Workflow

The project expects PHP 8.5, Composer, Node, and npm. The devcontainer is the recommended workspace because it includes PHP extensions, browser tooling, database clients, and optional services used by LPWork's configurable drivers.

Common commands:

```bash
composer install
npm ci
php lpwork about
php lpwork health:check
php lpwork test:lpwork
php lpwork check
php lpwork format --backend
npm run frontend:check
```

Use `php lpwork test` for application module tests and `php lpwork test:lpwork` for framework tests. Browser tests are available through `php lpwork test:lpwork --browser` after browser tooling is installed.

## CI And Release Confidence

GitHub Actions installs PHP and Node dependencies, then runs the framework verification suite:

```bash
php lpwork test:lpwork
php lpwork check
```

Before a public release tag, local maintainer hooks and CI should both pass. The immutable `v1.0.0` tag and installer release archive URLs should only be created after the first `main` push and a successful CI run.

## License

This is not open source software under MIT, Apache, GPL, or a similar reuse license. The code is source-available for portfolio/CV review only. See [LICENSE.md](LICENSE.md) for the current permission-required terms.
