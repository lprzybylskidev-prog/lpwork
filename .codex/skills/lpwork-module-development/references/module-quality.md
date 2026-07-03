# LPWork Module Quality

Use this reference before reporting module work complete.

## Test Placement

Backend tests belong in `App/Modules/<Module>/tests/backend`.

Frontend tests belong in `App/Modules/<Module>/tests/frontend` when the module has frontend assets.

Keep tests focused on observable behavior through framework boundaries such as HTTP, CLI, configuration, persistence, cache, session, queues, scheduled work, mail, notifications, security, and frontend tooling.

## Focused Verification

Run the narrowest meaningful command first:

- `php lpwork test --module=<Module>` for a module-contained change.
- `php lpwork test --module=<Module> --backend` for backend-only module work.
- `php lpwork test --module=<Module> --frontend` for frontend-only module work.

Broaden to `php lpwork test` when shared application behavior, shared config, provider registration, or cross-module behavior changed.

## Static Analysis And Formatting

Run `php lpwork check` when PHP types, providers, configuration, containers, command contracts, architecture boundaries, or public APIs changed.

Run `php lpwork format` when PHP files were added or imports/signatures/formatting changed.

Treat PHPStan/static-analysis failures as design feedback and fix the underlying type or boundary issue.

## Frontend And Browser Checks

Run `php lpwork frontend:check` when touching module TypeScript, CSS, assets, frontend tests, browser tooling, or frontend build configuration.

Use `npm run frontend:format` when frontend formatting changed or tooling can fix lint/style issues.

Use browser checks for changed rendered views, interactions, responsive layout, or asset loading.

## Module Self-Review

- Is each loadable piece explicitly registered?
- Does the module own the behavior, or should it be shared under `App/Shared`?
- Are providers focused on registration?
- Are side effects isolated behind commands, workers, emitters, storage, queue, or service boundaries?
- Are docs or config comments updated when usage changed?
