# Testing

LPWork separates application tests from framework tests. Application tests belong to modules.

## Test Locations

Backend tests:

```text
App/Modules/<Module>/tests/backend
```

Frontend tests:

```text
App/Modules/<Module>/tests/frontend
```

Backend-only modules created with `php lpwork make:module <Name> --no-frontend` should not have frontend entrypoints or frontend tests.

## Test Commands

| Command | Use |
| --- | --- |
| `php lpwork test --module=Blog` | Run backend and frontend tests for one module. |
| `php lpwork test --module=Blog --backend` | Run backend tests for one module. |
| `php lpwork test --module=Blog --frontend` | Run frontend tests for one module. |
| `php lpwork test --backend` | Run all application backend tests. |
| `php lpwork test --frontend` | Run all application frontend tests. |
| `php lpwork test` | Run the full application test suite. |
| `php lpwork check` | Run static analysis and frontend quality checks. |
| `php lpwork test:lpwork` | Run framework tests when intentionally changing `LPWork`. |

Use focused commands first, then broaden before finishing shared or risky changes.

## What To Test

Prefer observable tests through framework boundaries:

- HTTP routes, controllers, middleware, request validation, responses, redirects, cookies, and sessions.
- CLI commands, arguments, options, output, exit codes, and production safety behavior.
- Configuration loading, environment requirements, and driver selection.
- Persistence, storage, cache, locks, queues, scheduler, mail, notifications, broadcasting, and security behavior.
- Frontend entrypoints, TypeScript/CSS behavior, and browser-visible UI behavior when relevant.

Avoid tests that only prove implementation details. If setup repeats across module tests, move it into module or application test support instead of copying it.

## Frontend Testing

Frontend unit tests use the module frontend test tree. `php lpwork test --frontend` runs the frontend test task through the configured package manager.

Use `php lpwork frontend:check` for TypeScript, lint, style, and Prettier checks. Use browser checks when a rendered view, interaction, responsive layout, or asset-loading behavior needs runtime verification.

## Static Analysis And Formatting

Run `php lpwork check` when changes affect PHP types, containers, configuration, providers, public APIs, or architecture boundaries.

Run `php lpwork format` when PHP files were added or when imports, signatures, or formatting changed.

Treat PHPStan/static-analysis failures as design feedback. Fix the underlying type, lifecycle, registration, or boundary issue instead of suppressing the error.
