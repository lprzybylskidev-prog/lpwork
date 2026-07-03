# Configuration

LPWork application configuration lives under `App/Shared/Configs`. Each file implements a framework config definition and returns structured values consumed by framework services.

Configuration should stay explicit. Add new application config through a config definition class and register it through `App/Shared/Configs/ConfigsProvider.php` or through a module provider that implements the framework config-definition provider contract.

## Working With Configuration

- Keep secrets in environment values, not committed config files.
- Use project-root-relative paths when a config value represents an application file or directory.
- Prefer existing driver names and config shapes before adding a new variant.
- Use `php lpwork config:show` to inspect loaded configuration.
- Use `php lpwork health:check` after changing runtime drivers or external service settings.
- Use `php lpwork check` after changing config types, providers, public config shape, or environment requirements.

Environment requirements declared by config classes are validated through the framework configuration boundary. Some dynamic drivers also read optional environment values with defaults; those values are documented below even when they are not strict bootstrap requirements.

## Config Files

| File | Key | Purpose |
| --- | --- | --- |
| `AppConfig.php` | `app` | Application environment, name, debug mode, URL, language, and timezone. |
| `BroadcastingConfig.php` | `broadcasting` | Broadcast connection and broadcast logging channel. |
| `CacheConfig.php` | `cache` | Named cache stores for framework and application use. |
| `DatabaseConfig.php` | `database` | Default database connection and optional query logging. |
| `ErrorConfig.php` | `error` | PHP error reporting/display flags and production error-page route. |
| `LockConfig.php` | `locks` | Distributed lock backend, cache store, and default TTL. |
| `LoggingConfig.php` | `logging` | Application/error log channels and fallback files. |
| `MailConfig.php` | `mail` | Default mail transport, sender identities, templates, and mail logging. |
| `MaintenanceConfig.php` | `maintenance` | Maintenance-mode state file and optional route. |
| `NotificationsConfig.php` | `notifications` | Database-backed notification storage. |
| `ObservabilityConfig.php` | `observability` | Metrics retention and reporters. |
| `QueueConfig.php` | `queue` | Queue connection, queue name, retries, and retention windows. |
| `RoutingConfig.php` | `routing` | Global, aliased, and grouped route middleware. |
| `ScheduleConfig.php` | `schedule` | Scheduler locking, run storage, and history retention. |
| `SecurityConfig.php` | `security` | App key, production environments, security profiles, request limits, and CSRF. |
| `SessionConfig.php` | `session` | Session driver and cookie settings. |
| `StorageConfig.php` | `storage` | Default storage disk and disk definitions. |
| `ThrottleConfig.php` | `throttle` | Rate-limit storage and HTTP/CLI throttle policies. |
| `ViewConfig.php` | `view` | View paths, cache store, and PHP view extension. |

## Core Application

`AppConfig.php` reads:

| Environment value | Meaning |
| --- | --- |
| `APP_ENV` | Environment name such as `local`, `testing`, or `production`. |
| `APP_NAME` | Application display name used by diagnostics and generated output. |
| `APP_DEBUG` | Enables debug behavior when true. |
| `APP_URL` | Base URL used by URL generation. |
| `APP_LANG` | Default application language. |
| `APP_TIMEZONE` | Timezone used by application time services. |

`SecurityConfig.php` also reads `APP_KEY`. Generate and store it as a secret. Production-sensitive commands and security behavior depend on the configured environment boundary, not ad hoc process checks.

## Database

`DB_CONNECTION` selects the default connection.

| Driver | Required values | External requirement |
| --- | --- | --- |
| `mysql` | `DB_MYSQL_HOST`, `DB_MYSQL_PORT`, `DB_MYSQL_DATABASE`, `DB_MYSQL_USERNAME`, `DB_MYSQL_PASSWORD`, `DB_MYSQL_CHARSET` | MySQL-compatible server and `pdo_mysql`. |
| `pgsql` | `DB_PGSQL_HOST`, `DB_PGSQL_PORT`, `DB_PGSQL_DATABASE`, `DB_PGSQL_USERNAME`, `DB_PGSQL_PASSWORD`, `DB_PGSQL_CHARSET` | PostgreSQL server and `pdo_pgsql`. |
| `sqlite` | `DB_SQLITE_DATABASE` | SQLite database file and PDO SQLite support. |

Query logging is controlled by `DB_LOG_QUERIES`, `DB_LOG_CHANNEL`, and `DB_LOG_LEVEL`.

## Cache, Locks, Throttle, And Session

`CACHE_STORE` selects the default cache store. The framework always declares `framework` and `views` file-backed stores. Dynamic store names can also come from `THROTTLE_CACHE_STORE`, `LOCK_STORE`, and `SESSION_CACHE_STORE`.

| Cache store | Values | Requirement |
| --- | --- | --- |
| file-backed default | local disk paths under framework storage | Writable local storage. |
| `redis` | `CACHE_REDIS_HOST`, `CACHE_REDIS_PORT`, `CACHE_REDIS_PASSWORD`, `CACHE_REDIS_DATABASE`, `CACHE_REDIS_TIMEOUT_SECONDS`, `CACHE_REDIS_PREFIX` | Redis server. |
| `database` | `CACHE_DATABASE_CONNECTION`, `CACHE_DATABASE_TABLE` | Database connection and cache table migration. |
| `apcu` | `CACHE_APCU_PREFIX` | APCu PHP extension. |

`LockConfig.php` uses `LOCK_DRIVER` and `LOCK_TTL_SECONDS`. The default lock driver is `cache`; `LOCK_STORE` falls back to `CACHE_STORE` and then `framework`.

| Lock driver | Values | Requirement |
| --- | --- | --- |
| `cache` | `LOCK_STORE`, `LOCK_TTL_SECONDS` | Configured cache store. |
| `redis` | `LOCK_REDIS_HOST`, `LOCK_REDIS_PORT`, `LOCK_REDIS_PASSWORD`, `LOCK_REDIS_DATABASE`, `LOCK_REDIS_TIMEOUT_SECONDS`, `LOCK_REDIS_PREFIX`, `LOCK_TTL_SECONDS` | Redis server. |
| `database` | `LOCK_DATABASE_CONNECTION`, `LOCK_DATABASE_TABLE`, `LOCK_TTL_SECONDS` | Database connection and locks table migration. |

`ThrottleConfig.php` uses `THROTTLE_STORAGE`, optional `THROTTLE_CACHE_STORE`, and per-policy values:

- `THROTTLE_HTTP_WEB_ENABLED`, `THROTTLE_HTTP_WEB_MAX_ATTEMPTS`, `THROTTLE_HTTP_WEB_DECAY_SECONDS`
- `THROTTLE_HTTP_API_ENABLED`, `THROTTLE_HTTP_API_MAX_ATTEMPTS`, `THROTTLE_HTTP_API_DECAY_SECONDS`
- `THROTTLE_CLI_ENABLED`, `THROTTLE_CLI_MAX_ATTEMPTS`, `THROTTLE_CLI_DECAY_SECONDS`

`SESSION_DRIVER` selects session storage.

| Session driver | Values | Requirement |
| --- | --- | --- |
| `memory` | none | Process-local only; useful for tests or short-lived flows. |
| `cache` | common cookie values plus `SESSION_CACHE_STORE` | Configured cache store. |
| `database` | common cookie values plus `SESSION_DATABASE_CONNECTION`, `SESSION_DATABASE_TABLE` | Database connection and session table migration. |
| `redis` | common cookie values plus `SESSION_REDIS_HOST`, `SESSION_REDIS_PORT`, `SESSION_REDIS_PASSWORD`, `SESSION_REDIS_DATABASE`, `SESSION_REDIS_TIMEOUT_SECONDS`, `SESSION_REDIS_PREFIX` | Redis server. |
| `php` | common cookie values plus `SESSION_USE_STRICT_MODE` | PHP session handler. |

Common cookie values are `SESSION_NAME`, `SESSION_LIFETIME`, `SESSION_PATH`, `SESSION_DOMAIN`, `SESSION_SECURE`, `SESSION_HTTP_ONLY`, and `SESSION_SAME_SITE`.

## Queue And Scheduler

`QUEUE_CONNECTION` selects `sync`, `database`, `redis`, or `sqs`.

| Queue driver | Values | Requirement |
| --- | --- | --- |
| `sync` | none | Runs jobs immediately in the current process. |
| `database` | `QUEUE_DATABASE_CONNECTION`, `QUEUE_DATABASE_TABLE` | Database connection and queue jobs migration. |
| `redis` | `QUEUE_REDIS_HOST`, `QUEUE_REDIS_PORT`, `QUEUE_REDIS_PASSWORD`, `QUEUE_REDIS_DATABASE`, `QUEUE_REDIS_TIMEOUT_SECONDS`, `QUEUE_REDIS_PREFIX` | Redis server. |
| `sqs` | `QUEUE_SQS_URL`, `QUEUE_SQS_REGION`, `QUEUE_SQS_ACCESS_KEY`, `QUEUE_SQS_SECRET_KEY` | AWS SQS or compatible queue service. |

Queue behavior also uses `QUEUE_NAME`, `QUEUE_MAX_ATTEMPTS`, `QUEUE_RETRY_AFTER_SECONDS`, `QUEUE_RETRY_DELAY_SECONDS`, `QUEUE_COMPLETED_RETENTION_SECONDS`, and `QUEUE_FAILED_RETENTION_SECONDS`.

`ScheduleConfig.php` uses:

- `SCHEDULE_LOCK_TTL_SECONDS`
- `SCHEDULE_DATABASE_CONNECTION`
- `SCHEDULE_RUNS_TABLE`
- `SCHEDULE_HISTORY_ENABLED`
- `SCHEDULE_HISTORY_RETENTION_SECONDS`

Scheduler database settings default to the main `DB_CONNECTION` and `schedule_runs` table.

## Mail, Notifications, And Broadcasting

`MAIL_TRANSPORT` selects `log`, `smtp`, `sendmail`, `ses`, or `mailgun`.

| Mail transport | Values | Requirement |
| --- | --- | --- |
| `log` | none | Records mail instead of delivering it. |
| `smtp` | `MAIL_SMTP_HOST`, `MAIL_SMTP_PORT`, `MAIL_SMTP_USERNAME`, `MAIL_SMTP_PASSWORD`, `MAIL_SMTP_ENCRYPTION`, `MAIL_SMTP_TIMEOUT_SECONDS` | SMTP server such as Mailpit locally or a real provider. |
| `sendmail` | `MAIL_SENDMAIL_COMMAND` | Local sendmail-compatible binary. |
| `ses` | `MAIL_SES_REGION`, `MAIL_SES_ACCESS_KEY`, `MAIL_SES_SECRET_KEY` | AWS SES credentials. |
| `mailgun` | `MAILGUN_DOMAIN`, `MAILGUN_SECRET`, `MAILGUN_ENDPOINT` | Mailgun account and API credentials. |

Sender identities use `MAIL_FROM`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `MAIL_SUPPORT_ADDRESS`, and `MAIL_SUPPORT_NAME`. Mail logging uses `MAIL_LOG_ENABLED`, `MAIL_LOG_CHANNEL`, and `MAIL_LOG_LEVEL`.

Notifications use `NOTIFICATIONS_DB_CONNECTION` and `NOTIFICATIONS_TABLE`, defaulting to the main database connection and `notifications`.

`BROADCAST_CONNECTION` selects a log-compatible connection, `redis`, or `pusher`.

| Broadcast driver | Values | Requirement |
| --- | --- | --- |
| log/default | `BROADCAST_LOG_CHANNEL` | Configured log channel. |
| `redis` | `BROADCAST_REDIS_HOST`, `BROADCAST_REDIS_PORT`, `BROADCAST_REDIS_PASSWORD`, `BROADCAST_REDIS_DATABASE`, `BROADCAST_REDIS_TIMEOUT_SECONDS`, `BROADCAST_REDIS_PREFIX` | Redis server. |
| `pusher` | `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_ENDPOINT` | Pusher or Pusher-compatible server such as Soketi. |

## Storage

`STORAGE_DISK` selects the default disk. `local` and `public` are always declared.

| Disk | Values | Requirement |
| --- | --- | --- |
| `local` | project `storage` directory | Writable local storage. |
| `public` | `public/storage` and URL `/storage` | Public web directory. |
| `memory` | none | Process-local temporary storage. |
| `s3` | `STORAGE_S3_BUCKET`, `STORAGE_S3_REGION`, `STORAGE_S3_ACCESS_KEY`, `STORAGE_S3_SECRET_KEY`, `STORAGE_S3_ENDPOINT`, `STORAGE_S3_PATH_STYLE` | AWS S3 or compatible service such as MinIO. |
| `ftp` | `STORAGE_FTP_HOST`, `STORAGE_FTP_USERNAME`, `STORAGE_FTP_PASSWORD`, `STORAGE_FTP_ROOT`, `STORAGE_FTP_PORT`, `STORAGE_FTP_TIMEOUT_SECONDS`, `STORAGE_FTP_SSL`, `STORAGE_FTP_PASSIVE` | FTP server. |
| `sftp` | `STORAGE_SFTP_HOST`, `STORAGE_SFTP_USERNAME`, `STORAGE_SFTP_PASSWORD`, `STORAGE_SFTP_ROOT`, `STORAGE_SFTP_PORT`, `STORAGE_SFTP_TIMEOUT_SECONDS` | SFTP server. |

## Observability And Logging

`LoggingConfig.php` declares file-backed `app`, `error`, and `stack` channels. The fallback files live under the local storage disk in `logs/`.

`METRICS_REPORTERS` is a comma-separated list. Supported reporters are:

| Reporter | Values | Requirement |
| --- | --- | --- |
| `null` | none | Disables external reporting. |
| `log` | none | Writes metrics through logging. |
| `prometheus` | `METRICS_PROMETHEUS_PATH` | Writable Prometheus exposition file path. |
| `statsd` | `METRICS_STATSD_HOST`, `METRICS_STATSD_PORT`, `METRICS_STATSD_PREFIX` | StatsD-compatible UDP collector. |

`METRICS_MEMORY_LIMIT` caps retained in-process measurements.

## Routing, Views, Maintenance, And Errors

`RoutingConfig.php` defines middleware aliases and groups. The default `signed` alias points to signed URL validation middleware. Add route middleware here when the middleware should be available by name in routes.

`ViewConfig.php` resolves application views from `resources/views`, uses the `views` cache store, and renders `.php` view files.

`MaintenanceConfig.php` stores maintenance state in `storage/framework/maintenance.json`. Set `route` when maintenance mode should render an application route.

`ErrorConfig.php` reads `ERROR_LOG_DIRECTORY`, `ERROR_REPORTING`, `ERROR_DISPLAY`, `ERROR_DISPLAY_STARTUP`, and `ERROR_LOG`. Set `production_route` when production error pages should link to an application route.

## Adding Application Configuration

1. Create a config definition class under `App/Shared/Configs` or inside the module that owns the setting.
2. Return structured arrays from `values()`; avoid ad hoc parsing in consumers.
3. Add environment requirements when a value must be present or typed at bootstrap.
4. Register the definition through `ConfigsProvider.php` or a module config provider.
5. Update this page and nearby inline config comments when the option is user-facing.
6. Run `php lpwork config:show`, focused tests, and `php lpwork check` when the config shape affects PHP consumers.
