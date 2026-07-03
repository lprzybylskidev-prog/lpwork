# LPWork Documentation

LPWork documentation explains how to build applications with the framework. It focuses on the public application surface: configuration, modules, providers, routing, runtime services, testing, CLI commands, development tooling, and extension points.

Internal framework architecture should appear here only when an application developer needs that detail to use a feature correctly.

## Documentation Map

- [Documentation standards](documentation-standards.md) describes the writing style, page boundaries, and PHPDoc rules used by LPWork documentation.
- [Configuration](configuration.md) documents application config files, supported drivers, environment values, defaults, and external service requirements.
- [Application workflow](application-workflow.md) maps the day-to-day flow for adding module behavior.
- [Modules](modules.md) explains module ownership, provider structure, generation, and explicit registration.
- [Routing and HTTP](routing-and-http.md) covers route declarations, controllers, requests, responses, middleware, and route inspection.
- [Views and assets](views-and-assets.md) covers namespaced PHP views, module frontend entrypoints, and Vite commands.
- [Validation](validation.md) covers validators, form requests, rules, custom rules, and validation failures.
- [Testing](testing.md) covers module test locations, command filters, frontend tests, static analysis, and formatting.
- [Persistence and storage](persistence-and-storage.md) covers database connections, migrations, seeders, query diagnostics, and storage disks.
- [Cache, session, and locks](cache-session-locks.md) covers configured cache stores, session drivers, atomic locks, and runtime support tables.
- [Queues and scheduler](queues-and-scheduler.md) covers job dispatch, workers, scheduled tasks, scheduler commands, and observability.
- [Mail, notifications, and broadcasting](messaging-and-broadcasting.md) covers outbound mail, notification channels, broadcast drivers, and external provider setup.
- [Security, health, and about](security-health-and-about.md) covers `APP_KEY`, security profiles, production safety, `health:check`, and `about`.
- [CLI and tooling](cli-and-tooling.md) covers built-in commands, generators, frontend/browser tooling, quality workflow, shell completion, and command contracts.
- [Devcontainer](devcontainer.md) covers local services, browser URLs, host ports, credentials, VS Code conveniences, and equivalent browser or CLI workflows.
- `docs/.AGENTS.md` is the generated application's root `AGENTS.md` template and routes coding agents into the LPWork application skills under `.codex/skills`.

## Application Boundaries

Applications should usually extend LPWork through `App`, Composer or npm dependencies, providers, configuration, routes, views, commands, migrations, and module code. Change `LPWork` itself only when the intended work is framework development.

LPWork applications are organized around `App/Modules/*`. A module may use the default generated folder layout or its own internal layout, as long as loadable pieces remain explicitly registered through the appropriate provider or configuration boundary.

LPWork favors explicit registration over magic discovery. Routes, providers, config definitions, views, translations, assets, events, commands, migrations, and other loadable pieces should be declared through the framework boundary that owns them.

LPWork is intentionally extensible rather than a closed ecosystem. Applications may integrate ORM libraries, Twig, Vue/React/Svelte, API tooling, auth packages, cloud SDKs, and other Composer or npm dependencies through application providers and configuration.
