# LPWork Quality Workflow

Use this reference before reporting application work complete.

## Tests

Run the narrowest meaningful test first:

- `php lpwork test --module=<Name>` for one module when the changed behavior is module-contained.
- `php lpwork test --backend` for backend-only application changes.
- `php lpwork test --frontend` for frontend-only application changes.
- `php lpwork test` before finishing broader application changes.

Use `php lpwork test:lpwork` only when the user asked for framework work or the change intentionally touches `LPWork`, framework-owned resources, skeleton generation, or framework tests.

## Static Analysis

Run `php lpwork check` when a change affects PHP types, configuration, containers, providers, architecture boundaries, public APIs, command contracts, or shared runtime services.

Treat PHPStan/static-analysis failures as design feedback. Fix the type, lifecycle, registration, generic shape, or boundary problem rather than suppressing it.

## Formatting

Run `php lpwork format` when PHP files were added or when imports, signatures, formatting, or generated PHP changed.

Use formatter output as a mechanical cleanup step. Do not mix unrelated formatting churn into a focused behavioral change when it can be avoided.

## Frontend Quality

Run `php lpwork frontend:check` when touching TypeScript, CSS, frontend module assets, browser tooling, or frontend build configuration.

Use `npm run frontend:format` when frontend formatting changed or lint/style tooling can fix the issue mechanically.

Run browser checks when the change affects UI behavior, rendered views, interactive controls, responsive layout, or assets that need runtime verification.

## Health And Diagnostics

Run `php lpwork health:check` when configuration, runtime drivers, storage, queues, cache, session, mail, broadcasting, frontend tooling, or devcontainer-backed services changed.

Run `php lpwork about` when a change affects framework/application metadata or diagnostics that should be visible to developers.

## Before Finishing

Do a short self-review:

- Does each touched class have one primary reason to change?
- Are side effects isolated behind framework/application boundaries?
- Are providers focused on registration?
- Are loadable pieces explicitly registered?
- Are tests covering observable behavior through the relevant boundary?
- Are docs or config comments updated for changed user-facing behavior?
