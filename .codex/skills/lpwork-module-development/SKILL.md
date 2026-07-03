---
name: lpwork-module-development
description: Use when adding or changing an LPWork application module under App/Modules, including module providers, routes, controllers, requests, views, assets, translations, commands, migrations, events, tests, registration, and module-specific verification.
---

# LPWork Module Development

Use this skill for work under `App/Modules/*`. Also use `.codex/skills/lpwork-application/SKILL.md` when module work touches shared configuration, runtime services, integrations, frontend tooling, or framework boundaries.

## Module Workflow

1. Identify the module that owns the behavior.
2. Inspect its providers, routes, controllers, views/assets, tests, and existing naming/layout before editing.
3. Add behavior inside the module unless it is clearly shared application code.
4. Register every loadable piece through the owning provider or configuration boundary.
5. Add or update observable tests under the module test tree.
6. Run focused module verification, then broaden when shared application behavior changed.

## Reference Map

Read these references when the task touches that area:

- `references/module-structure.md`: module ownership, default layout, custom layout rules, providers, routes, controllers, views/assets, commands, events, migrations, and translations.
- `references/module-quality.md`: module tests, backend/frontend filters, PHPStan/static analysis, formatting, frontend checks, browser checks, and when to broaden to application/framework verification.

## Registration Rules

LPWork prefers explicit registration over discovery.

- Register HTTP routes through the module route provider.
- Register services, commands, migrations, translations, views, assets, events, and listeners through the provider or configuration boundary that owns them.
- Keep providers focused on registration. If provider code begins parsing, validating, constructing multiple variants, or performing side effects, introduce a small collaborator.

## Test Locations

Backend tests belong in `App/Modules/<Module>/tests/backend`. Frontend tests belong in `App/Modules/<Module>/tests/frontend` when the module has frontend assets.

Run the narrowest meaningful command first, such as `php lpwork test --module=<Module>`, and broaden to `php lpwork test`, `php lpwork check`, or frontend/browser checks when the change crosses module boundaries.
