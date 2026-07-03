# Security, Health, And Runtime Diagnostics

LPWork runtime services are tied together by security configuration, health checks, and about diagnostics. These surfaces help application code stay explicit about secrets, production safety, and operational state.

## Application Key

`APP_KEY` signs and protects framework security values. Generate it through the framework command boundary:

```bash
php lpwork key:generate
```

Do not commit a real production key. Commands that would overwrite existing secrets or production-sensitive state should require explicit force behavior when the framework command supports it.

## Security Profiles

`App/Shared/Configs/SecurityConfig.php` selects the active environment from `APP_ENV` and maps it to security profiles.

The generated profiles distinguish development and production behavior:

| Setting Area | Purpose |
| --- | --- |
| HTTPS and secure cookies | Enforce transport and cookie safety in production. |
| Security headers | Send framework-managed headers where the profile enables them. |
| Trusted hosts and proxies | Scope host/proxy trust to known deployment boundaries. |
| Request and upload size limits | Reject oversized request bodies before application logic handles them. |
| CSRF | Protect state-changing browser requests with configured token behavior. |

Use framework security helpers and middleware instead of reading globals or mutating headers directly from controllers and services.

## Production Safety

LPWork detects production through the configured environment boundary. Production-sensitive commands refuse unsafe behavior unless the command exposes and receives `--force`.

Examples:

| Command | Production behavior |
| --- | --- |
| `php lpwork migrate --seed` | Requires `--force` because it runs seeders. |
| `php lpwork migrate:rollback` | Requires `--force` because it mutates schema state. |
| `php lpwork migrate:fresh` | Requires `--force` because it rebuilds schema state. |
| `php lpwork db:seed` | Requires `--force` because it mutates stored data. |
| `php lpwork cache:clear` | Requires `--force` because it clears runtime cache state. |
| `php lpwork queue:clear` | Requires `--force` because it deletes queued jobs. |
| `php lpwork schedule:prune` | Requires `--force` because it mutates scheduler storage. |

Application commands that mutate secrets, schema, stored data, cache state, queues, or other persistent runtime state should make the side effect visible in the command name, description, options, and tests.

## Health Checks

`php lpwork health:check` runs core runtime dependency checks. It is the first command to run after changing drivers, credentials, service hostnames, devcontainer services, or required PHP extensions.

Health coverage includes framework modules such as database, cache, locks, queue, scheduler, mail, notifications, broadcasting, security, storage, routing, views, frontend build tooling, logging, runtime directories, testing, and translation where those modules are registered.

Use health checks as a deployment and local setup signal. A passing health check does not replace feature tests, but it catches missing tables, broken service connections, unwritable runtime directories, and unavailable external dependencies earlier than normal traffic would.

## About Diagnostics

`php lpwork about` displays runtime, cache, and framework module information.

Use it when orienting a project, debugging configuration drift, or verifying which framework modules and drivers are active. About output should not expose secrets. If a diagnostic needs to show connection information, it should use non-secret endpoint or driver summaries.

## Debug Context And Logs

Runtime modules may contribute query, cache, queue, scheduler, and other diagnostic records to the debug context. Logging should stay scoped and avoid sensitive values by default.

Follow these rules in application code:

- Log lifecycle and failure context, not secrets or full payloads.
- Keep high-volume debug logging disabled by default or behind explicit configuration.
- Use events, listeners, collectors, or reporters for optional observability instead of scattering log calls through unrelated orchestration code.
- Treat health checks, about output, logs, and debug context as complementary surfaces. Do not hide required control flow in diagnostics hooks.
