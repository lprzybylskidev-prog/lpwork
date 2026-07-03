# CLI And Tooling

LPWork exposes framework, application, and development tooling through one entrypoint:

```bash
php lpwork <command> [arguments] [options]
```

Use `php lpwork --help` for workflow-oriented help and `php lpwork` for the compact command catalog. Use `php lpwork <command> --help` before relying on command options in scripts or documentation.

## Input Conventions

| Syntax | Meaning |
| --- | --- |
| `<name>` | Required argument. |
| `[name]` | Optional argument. |
| `--flag` | Boolean option. |
| `--name=VALUE` | Named value option. |
| `--tag=VALUE...` | Repeatable named value option. |
| `--` | Stop parsing options and pass following tokens as arguments. |

Global options:

| Option | Use |
| --- | --- |
| `-h`, `--help` | Show global or command-specific help. |
| `--module=VALUE` | Set the application module context for generator commands. |

Prefer command-specific `--module=VALUE` or global `--module=VALUE` consistently in scripts. The generated application examples usually place it before the generator command:

```bash
php lpwork --module=Blog make:controller PostController
```

## Core Diagnostics

| Command | Use |
| --- | --- |
| `php lpwork about` | Display runtime, cache, and framework module information. |
| `php lpwork health:check` | Run runtime dependency health checks. |
| `php lpwork key:generate` | Generate and store the framework `APP_KEY` secret. |

Run `health:check` after changing drivers, service credentials, required PHP extensions, or local infrastructure. Run `about` when orienting a project or checking active runtime modules.

## Configuration Commands

| Command | Use |
| --- | --- |
| `php lpwork config:show` | Display loaded configuration values with secrets redacted. |
| `php lpwork config:show database` | Display one configuration key. |
| `php lpwork config:show --show-secrets` | Display sensitive values intentionally. Use carefully. |
| `php lpwork config:validate` | Validate required environment values declared by config definitions. |
| `php lpwork config:cache` | Rebuild compiled configuration cache. |
| `php lpwork config:clear` | Clear compiled configuration cache. |

Use `config:validate` before debugging service behavior. It catches missing or invalid environment values before runtime code reaches a driver.

Compiled config cache is an optimization and a deployment artifact. Clear or rebuild it after changing config definitions or environment values used during cache generation.

## Quality Workflow

| Command | Use |
| --- | --- |
| `php lpwork test` | Run the full application test suite. |
| `php lpwork test --module=Blog` | Run backend and frontend tests for one module. |
| `php lpwork test --backend` | Run application backend tests. |
| `php lpwork test --frontend` | Run application frontend tests. |
| `php lpwork test:lpwork` | Run framework tests when intentionally changing `LPWork`. |
| `php lpwork test:lpwork --browser` | Run framework tests and framework Playwright tests. |
| `php lpwork check` | Run backend static analysis and frontend quality checks. |
| `php lpwork check --backend` | Run only backend check work. |
| `php lpwork check --frontend` | Run only frontend check work. |
| `php lpwork format` | Format project PHP files with PHP CS Fixer. |
| `php lpwork format --backend` | Format backend PHP files only. |
| `php lpwork coverage` | Run the Pest test suite with coverage. |

Use focused tests first while iterating. Before finishing shared behavior, runtime services, configuration, framework code, or generator changes, broaden to `php lpwork check` and the relevant full test command.

Treat PHPStan and architecture failures as design feedback. Fix the type, lifecycle, boundary, provider registration, or public API shape instead of suppressing the failure.

## Generator Workflow

Generators create application-owned files. They are meant for `App` and module code, not framework internals.

Common generators:

| Command | Use |
| --- | --- |
| `php lpwork make:module Blog` | Create and register a module skeleton. |
| `php lpwork make:module Blog --no-frontend` | Create a backend-only module. |
| `php lpwork make:module Blog --no-register` | Create files without adding the module to `AppServiceProvider`. |
| `php lpwork --module=Blog make:controller PostController` | Create an HTTP controller in a module. |
| `php lpwork --module=Blog make:command ImportPostsCommand --register` | Create and register an application command. |
| `php lpwork --module=Blog make:migration CreatePostsTable --connection=default --register` | Create and register a migration. |
| `php lpwork --module=Blog make:form-request StorePostRequest` | Create a form request. |
| `php lpwork --module=Blog make:job PublishPost` | Create a queued job. |
| `php lpwork --module=Blog make:notification PostPublished` | Create a mail notification. |

Most file generators support:

| Option | Use |
| --- | --- |
| `--module=VALUE` | Place the generated file in a module. |
| `--path=VALUE` | Override the output directory. |
| `--namespace=VALUE` | Use a namespace for paths outside the normal app/framework layout. |
| `--register` | Register the generated class in its provider when supported. |

Use `--register` when the generated artifact should be immediately loadable by the framework, such as commands, routes, migrations, seeders, providers, middleware, listeners, health checks, broadcast channel providers, validation rules, and view extensions.

After generating files, inspect the owning provider. LPWork favors explicit registration, so generated code should leave the module graph understandable instead of relying on directory scanning.

## Routing, Views, And Translation Caches

| Command | Use |
| --- | --- |
| `php lpwork route:list` | Display registered application routes. |
| `php lpwork route:cache` | Compile application routes into a cache file. |
| `php lpwork route:clear` | Clear compiled route cache. |
| `php lpwork view:clear` | Clear cached view lookup data. |
| `php lpwork translation:cache` | Compile translation files into a cache file. |
| `php lpwork translation:clear` | Clear compiled translation cache. |

Use `route:list` after adding routes or middleware aliases. Rebuild route and translation caches when deployed environments use compiled caches.

## Runtime Operations

Runtime operation commands are documented in the related runtime pages:

| Area | Commands |
| --- | --- |
| Database | `migrate:status`, `migrate`, `migrate:rollback`, `migrate:fresh`, `db:seed` |
| Cache | `cache:clear`, `cache:rebuild` |
| Queue | `queue:work`, `queue:clear`, `queue:prune` |
| Scheduler | `schedule:list`, `schedule:run`, `schedule:prune` |
| Maintenance | `maintenance:down`, `maintenance:up`, `maintenance:status` |

Production-sensitive commands make destructive or persistent side effects explicit and require `--force` where the framework command boundary supports it. Examples include cache clearing, queue clearing, migration rollback/fresh, production seeding, and schedule pruning.

Use `maintenance:down --retry=60` to put the application into maintenance mode with a `Retry-After` value. Use `maintenance:up` to resume normal traffic and `maintenance:status` to inspect the current state.

## Frontend Tooling

Frontend commands wrap the configured package manager and npm scripts.

| Command | Use |
| --- | --- |
| `php lpwork frontend:install` | Install frontend dependencies. |
| `php lpwork frontend:entries` | Render declared Vite entrypoints. |
| `php lpwork frontend:dev` | Run the Vite development server. |
| `php lpwork frontend:build` | Build frontend assets with Vite. |
| `php lpwork frontend:clean` | Clean generated frontend artifacts. |
| `php lpwork frontend:format` | Format frontend files. |
| `php lpwork frontend:check` | Run TypeScript, ESLint, Stylelint, and Prettier checks. |
| `php lpwork frontend:test` | Run frontend unit tests. |

Use `frontend:dev` while working on local browser-rendered assets. Use `frontend:build` before verifying production-style asset loading.

## Browser Tooling

Browser commands wrap Playwright.

| Command | Use |
| --- | --- |
| `php lpwork browser:install` | Install Playwright browsers. |
| `php lpwork browser:test` | Run Playwright browser tests. |
| `php lpwork browser:ui` | Open the Playwright test UI. |

Use browser checks when changing rendered UI, responsive layout, interactive behavior, asset loading, framework-owned UI, or frontend flows that static checks cannot prove.

## Shell Completion

| Command | Use |
| --- | --- |
| `php lpwork completion:install` | Install completion for the current shell when detected. |
| `php lpwork completion:install zsh` | Install completion for a specific shell. |
| `php lpwork completion:generate bash` | Print a shell completion script. |

Supported shells are `bash`, `zsh`, and `fish`.

## Command Contracts

When adding or changing a user-facing command:

- Keep the command name, description, arguments, options, help text, exit codes, and completion behavior aligned.
- Update `php lpwork --help` expectations and command tests in the same change.
- Preserve meaningful exit codes from external tools.
- Make persistent side effects visible in the command name, description, options, and tests.
- Use the shared production-safety boundary for production-sensitive behavior.
- Add tests for safe refusal in production, success with `--force`, and normal development behavior when the command mutates sensitive or persistent state.
