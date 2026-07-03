# LPWork Application Navigation

Use this reference to build a working map before editing an LPWork application.

## Top-Level Ownership

- `App`: application composition and application-owned behavior.
- `App/Modules/*`: module-owned routes, controllers, requests, views, assets, translations, commands, migrations, events, listeners, and tests.
- `App/Shared/Configs`: application configuration declarations.
- `docs`: application-facing documentation.
- `.codex/skills`: agent guidance installed with the generated application.
- `LPWork`: framework internals. Treat this as a dependency unless the user asks for framework development.

## Before Editing

Inspect the closest existing pattern:

- For module behavior, read the module provider graph, route declarations, controllers, views/assets, and tests.
- For configuration, read `App/Shared/Configs` and the config provider that declares the file.
- For commands, run `php lpwork --help` and `php lpwork <command> --help`, then read the command/provider that owns the registration.
- For runtime services, inspect the configured driver and the provider/manager boundary that consumes it.
- For views or frontend assets, inspect the module asset/view providers and existing frontend test or browser-test shape.
- For command usage, generators, quality workflow, frontend/browser tooling, and command contracts, read `docs/cli-and-tooling.md`.
- For local service URLs, devcontainer ports, credentials, and browser/CLI tooling, read `docs/devcontainer.md`.

## Common Commands

- `php lpwork --help`: global command map and workflow overview.
- `php lpwork <command> --help`: command-specific arguments and options.
- `php lpwork about`: project/framework orientation and diagnostics.
- `php lpwork health:check`: configured runtime health.
- `php lpwork config:show`: loaded configuration overview.

## Application vs Framework Decision

Choose `App` when the change is specific to the generated application, a module, an integration, or a project convention.

Choose `LPWork` only when the user asks to change framework behavior, a reusable framework capability, framework tests, built-in commands, framework-owned resources, or skeleton generation behavior.
