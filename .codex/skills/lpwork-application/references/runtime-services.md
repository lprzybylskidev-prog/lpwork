# LPWork Runtime Services

Use this reference when application work touches configured runtime services or integrations.

## Configuration First

Runtime services should be selected and configured through `App/Shared/Configs` and environment values. Avoid hard-coded service names, paths, hosts, ports, or credentials in module code.

When a feature depends on an external service, document the required driver, environment values, credentials, hostnames, ports, queues, buckets, disks, or transports near the configuration and in docs when user-facing setup is affected.

## Persistence And Storage

Use framework database/storage boundaries where available. Migrations belong to the module or application area that owns the schema. Storage disks and public URLs should come from configuration.

Avoid writing direct filesystem paths in module internals when a configured storage disk or application path abstraction should own the path.

Primary docs:

- `docs/persistence-and-storage.md`
- `docs/configuration.md`

## Cache, Session, Locks, Queue, Scheduler

Use configured stores/drivers rather than constructing drivers inline. Queue and scheduled work should make lifecycle, retry/exit behavior, side effects, and observable progress explicit.

Commands that mutate persistent state should make the side effect clear and should use the framework safety boundary when production-sensitive.

Primary docs:

- `docs/cache-session-locks.md`
- `docs/queues-and-scheduler.md`

## Mail, Notifications, Broadcasting

Register messages, notifications, channels, and listeners explicitly through the owning module/application provider. Keep optional reactions in events/listeners when they are not required for the main result.

Do not log sensitive values by default. Follow the app debug boundary for secrets, tokens, email addresses, payloads, and credentials.

Primary docs:

- `docs/messaging-and-broadcasting.md`

## Security

Use framework security helpers and configured secrets. Do not overwrite secrets or persistent values by default. Production-sensitive operations should require explicit force behavior where the framework command boundary supports it.

Primary docs:

- `docs/security-health-and-about.md`

## Observability

Consider whether a runtime behavior should be visible through logs, health checks, `about`, debug context, or UI diagnostics. Prefer focused collectors/reporters/listeners over scattering logging and debug mutations through orchestration code.
