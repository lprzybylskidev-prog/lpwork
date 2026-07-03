---
name: lpwork-application
description: Use when working in an LPWork generated application: application/framework boundaries, configuration, providers, routing, runtime services, integrations, testing, formatting, PHPStan/static analysis, frontend checks, documentation, or deciding whether work belongs in App or LPWork.
---

# LPWork Application

Use this skill for LPWork application work outside a single narrow module task, and whenever you need the framework mental model before editing.

## First Moves

1. Treat `LPWork` as framework code and `App` as application code unless the user explicitly asks for framework development.
2. Read the relevant provider, route, config, view, command, and test files before editing.
3. Prefer explicit registration over magic discovery for routes, providers, config definitions, views, translations, assets, events, commands, migrations, and other loadable files.
4. Use existing LPWork patterns before introducing a new abstraction.
5. Keep changes scoped to the user request and update tests/docs when the usage contract changes.

## Reference Map

Read these references when the task touches that area:

- `references/navigation.md`: codebase map, ownership boundaries, where to look before editing, and common file locations.
- `references/architecture.md`: design rules for providers, registration, configuration, exceptions, boundaries, globals, and framework extension.
- `references/quality.md`: verification workflow for `php lpwork test`, `php lpwork check`, PHPStan, `php lpwork format`, frontend checks, browser checks, and framework tests.
- `references/runtime-services.md`: persistence, cache, session, locks, queues, scheduler, mail, notifications, broadcasting, security, storage, health/about, and integrations.

For module-specific work under `App/Modules/*`, also read `.codex/skills/lpwork-module-development/SKILL.md`.

## Application Boundary

Applications should usually extend LPWork through `App`, Composer/npm dependencies, providers, configuration, routes, views, commands, migrations, events, listeners, module tests, and docs. Modify `LPWork` only when the user asks for framework behavior changes.

When the application integrates external libraries such as an ORM, Twig, Vue/React/Svelte, API tooling, auth packages, or cloud SDKs, wire them through application providers and configuration. Do not create framework abstractions unless the user wants the framework itself to grow.

## Quality Gate

Always run focused tests for touched behavior. Before finishing larger application work, run `php lpwork test` and use `php lpwork check` when PHP types, configuration, containers, providers, architecture boundaries, or public APIs changed. Run `php lpwork format` after PHP import/signature/formatting changes. Frontend changes require `php lpwork frontend:check` or the matching npm check/format command.
