# Persistence And Storage

LPWork separates relational persistence from file/object storage. Database connections are configured through `database`; storage disks are configured through `storage`.

Use database connections for schema-backed application state. Use storage disks for files, generated artifacts, uploads, logs, exports, and object-store integrations.

## Database Connections

`App/Shared/Configs/DatabaseConfig.php` selects the default database connection from `DB_CONNECTION`.

Supported built-in database drivers are:

| Driver | Use |
| --- | --- |
| `sqlite` | Local development, tests, and lightweight applications. |
| `mysql` | MySQL-compatible servers through `pdo_mysql`. |
| `pgsql` | PostgreSQL servers through `pdo_pgsql`. |

Resolve database access through the container by asking for `LPWork\Database\DatabaseManager` or the default `LPWork\Database\Contracts\Connection`.

```php
use LPWork\Database\Contracts\Connection;

final readonly class UserReport
{
    public function __construct(private Connection $db) {}

    public function activeCount(): int
    {
        return (int) $this->db->selectOne('select count(*) as total from users where active = 1')['total'];
    }
}
```

Use `DatabaseManager` when code must choose a named connection:

```php
use LPWork\Database\DatabaseManager;

$connection = $databases->connection('reporting');
```

Keep connection names in configuration or module-level constants. Do not hard-code hosts, ports, database files, credentials, or DSNs in module code.

## Migrations And Seeders

Application and module schema should be declared through the owning migration provider. Framework-owned support tables, such as cache, queue, scheduler, session, lock, and notification tables, are registered by their framework modules when the matching driver requires them.

Useful commands:

| Command | Use |
| --- | --- |
| `php lpwork migrate:status` | Show registered migration state. |
| `php lpwork migrate` | Run pending migrations for the default connection. |
| `php lpwork migrate --connection=reporting` | Run migrations for one named connection. |
| `php lpwork migrate --all` | Run migrations for all registered connections. |
| `php lpwork migrate --seed` | Run seeders after migrations. Requires `--force` in production. |
| `php lpwork migrate:rollback` | Roll back the latest batch. Requires `--force` in production. |
| `php lpwork migrate:fresh` | Roll back all migrations and run them again. Requires `--force` in production. |
| `php lpwork db:seed` | Run registered seeders. Requires `--force` in production. |

`migrate:fresh` is destructive because it rebuilds schema state. Use it for development and controlled reset workflows, not as a normal production deploy command.

## Query Diagnostics

Database query reporting is configured by `DB_LOG_QUERIES`, `DB_LOG_CHANNEL`, and `DB_LOG_LEVEL`.

When query logging is enabled, LPWork logs query metadata through the configured logging channel. In debug mode it may include more diagnostic detail. Keep query logging disabled or carefully scoped in production because SQL and bindings can reveal sensitive application data.

Database activity is also exposed through the debug context and runtime diagnostics where the active framework modules support it.

## Storage Disks

`App/Shared/Configs/StorageConfig.php` selects the default disk from `STORAGE_DISK`. The generated application always declares `local` and `public`, and can select optional drivers through environment values.

Supported built-in storage drivers are:

| Driver | Use |
| --- | --- |
| `local` | Files under a configured project-relative root. |
| `memory` | Process-local temporary storage for tests and short-lived flows. |
| `s3` | AWS S3 or S3-compatible services such as MinIO. |
| `ftp` | FTP-backed storage. |
| `sftp` | SSH/SFTP-backed storage. |

Resolve `LPWork\Storage\StorageManager` for named disks or `LPWork\Storage\StorageDisk` for the default disk.

```php
use LPWork\Storage\StorageManager;

$disk = $storage->disk('public');
$disk->put('avatars/user-42.txt', $contents);

$url = $storage->url('avatars/user-42.txt', 'public');
```

`StorageDisk` supports `exists`, `get`, `put`, `putIfMissing`, `append`, `delete`, `clear`, `withExclusiveLock`, and `url`.

Only disks with a configured `url` can create public URLs. The generated `public` disk uses the `/storage` URL prefix. Private disks should not expose URLs unless the application intentionally configures a public endpoint.

## Storage Boundaries

- Store upload paths, bucket names, endpoints, roots, credentials, and public URL bases in configuration.
- Prefer storage disks over manual filesystem paths when files are part of application runtime behavior.
- Use `withExclusiveLock` for write flows that must not interleave on the same storage path.
- Keep generated files, exports, and uploads in the module or service that owns their lifecycle.
