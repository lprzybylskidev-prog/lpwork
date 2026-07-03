# Devcontainer

The LPWork devcontainer is a VS Code development workspace. It is not a production container and does not define framework runtime requirements.

Use it to get a complete local environment for PHP, Composer, Node, npm, browser tooling, databases, Redis, SMTP, S3/SQS-compatible services, websocket broadcasting, metrics, and browser-based inspection tools.

## Main Services

| Service | Internal hostname | Host URL or port | Use |
| --- | --- | --- | --- |
| `php` | `php` | debugbar websocket on `localhost:8082` | PHP 8.5 FPM workspace, Composer, Node/npm, Playwright/Chromium, PHP extensions, and CLI tools. |
| `nginx` | `nginx` | `http://localhost:8080` | Serves the application from `public/` through PHP-FPM. |
| `database` | `database:5432` | `localhost:54320` | PostgreSQL 17 for `pgsql` database connections. |
| `mysql` | `mysql:3306` | `localhost:33060` | MySQL 8.4 for `mysql` database connections. |
| `redis` | `redis:6379` | `localhost:63790` | Redis for cache, session, lock, queue, broadcast, or throttle drivers. |
| `adminer` | `adminer:8080` | `http://localhost:18083` | Browser database UI for PostgreSQL and MySQL. |
| `redis-commander` | `redis-commander:8081` | `http://localhost:18084` | Browser Redis inspection UI. |
| `mailpit` | `mailpit:1025` SMTP, `mailpit:8025` UI | `localhost:1025`, `http://localhost:8025` | Local SMTP capture and mail preview. |
| `minio` | `minio:9000` API, `minio:9001` UI | `http://localhost:9000`, `http://localhost:9001` | S3-compatible object storage. |
| `localstack` | `localstack:4566` | `http://localhost:4566` | Local AWS-compatible SQS and S3 endpoint. |
| `soketi` | `soketi:6001` | `http://localhost:6001` | Pusher-compatible websocket broadcasting. |
| `statsd` | `statsd:9125` UDP/TCP, `statsd:9102` metrics | `localhost:8125`, `http://localhost:9102` | StatsD ingestion and Prometheus-style metrics export. |

Use internal hostnames from application configuration inside the devcontainer. Use host URLs from your browser, database client, Redis client, or external terminal.

## Credentials

Default local credentials are intentionally simple and development-only.

| Service | Credentials |
| --- | --- |
| PostgreSQL | database `lpwork`, user `lpwork`, password `lpwork` |
| MySQL | database `lpwork`, user `lpwork`, password `lpwork`, root password `lpwork` |
| Redis | no password by default |
| Mailpit | no authentication by default |
| MinIO | user `lpwork`, password `lpwork-minio` |
| LocalStack | test credentials from `.env.example`, region `us-east-1` |
| Soketi | app id `lpwork`, key `lpwork-key`, secret `lpwork-secret` |

Do not copy these credentials into production or shared environments.

## Browser Tools

Use browser tools when you want quick visual inspection without installing host-side clients:

| Tool | URL | Use |
| --- | --- | --- |
| Application | `http://localhost:8080` | Browse the LPWork application through nginx. |
| Vite dev server | `http://localhost:5173` | Frontend development server from `php lpwork frontend:dev`. |
| Debugbar websocket | `localhost:8082` | Debugbar websocket endpoint exposed by the PHP service. |
| Adminer | `http://localhost:18083` | Inspect PostgreSQL or MySQL tables and run SQL manually. |
| Redis Commander | `http://localhost:18084` | Inspect Redis keys used by cache, queue, locks, sessions, or broadcasts. |
| Mailpit | `http://localhost:8025` | Inspect mail sent through the local SMTP transport. |
| MinIO console | `http://localhost:9001` | Inspect S3-compatible buckets and objects. |
| LocalStack edge | `http://localhost:4566` | Local AWS-compatible endpoint for SDKs and CLI tools. |
| Soketi | `http://localhost:6001` | Pusher-compatible websocket endpoint. |
| Soketi metrics | `http://localhost:9601` | Soketi metrics endpoint. |
| StatsD metrics | `http://localhost:9102` | Metrics exported by the StatsD exporter. |

VS Code extensions are convenience tooling. Equivalent browser or CLI workflows should remain available where practical.

## CLI Tools In The PHP Container

The PHP container includes:

- PHP 8.5 FPM with `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`, `ftp`, `intl`, `zip`, `apcu`, `redis`, `ssh2`, and `xdebug`.
- Composer 2.
- Node.js and npm.
- Chromium, Xvfb, and browser dependencies for Playwright.
- Database and service clients: `psql`, `mysql`/MariaDB client, `sqlite3`, `redis-cli`, `lftp`, OpenSSH/SFTP client, and `msmtp`.
- Development utilities such as Git, `ripgrep`, sudo, unzip, and zip.

The `postCreateCommand` runs `composer install`, `npm install`, and `.devcontainer/setup-lpwork-shell.sh`.

The shell setup script:

- Creates `~/.local/bin/lpwork` as a symlink to `/workspace/lpwork`.
- Adds `~/.local/bin` to `PATH`.
- Enables bash completion from `lpwork completion:generate bash`.

## LocalStack Resources

LocalStack starts with SQS and S3 enabled. The ready hook creates:

| Resource | Value |
| --- | --- |
| SQS queue | `lpwork` |
| SQS URL | `http://localstack:4566/000000000000/lpwork` |
| S3 bucket | `lpwork` |
| Region | `us-east-1` |

Use LocalStack for queue or storage flows that need AWS-compatible behavior without real AWS credentials.

## MinIO Versus LocalStack

Both MinIO and LocalStack can be used for S3-compatible storage:

- Use MinIO when you want a browser console and object-storage focused workflow.
- Use LocalStack when the feature also touches AWS-style services such as SQS.

Point LPWork storage at the endpoint you intend to exercise through `STORAGE_S3_ENDPOINT` and matching credentials.

## Runtime Configuration Examples

Examples for switching local drivers:

```dotenv
DB_CONNECTION=pgsql
DB_PGSQL_HOST=database
DB_PGSQL_PORT=5432
DB_PGSQL_DATABASE=lpwork
DB_PGSQL_USERNAME=lpwork
DB_PGSQL_PASSWORD=lpwork
```

```dotenv
CACHE_STORE=redis
CACHE_REDIS_HOST=redis
CACHE_REDIS_PORT=6379
```

```dotenv
MAIL_TRANSPORT=smtp
MAIL_SMTP_HOST=mailpit
MAIL_SMTP_PORT=1025
MAIL_SMTP_ENCRYPTION=
```

```dotenv
QUEUE_CONNECTION=sqs
QUEUE_SQS_URL=http://localstack:4566/000000000000/lpwork
QUEUE_SQS_REGION=us-east-1
QUEUE_SQS_ACCESS_KEY=test
QUEUE_SQS_SECRET_KEY=test
```

```dotenv
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=lpwork
PUSHER_APP_KEY=lpwork-key
PUSHER_APP_SECRET=lpwork-secret
PUSHER_ENDPOINT=http://soketi:6001
```

After changing driver configuration, run:

```bash
php lpwork config:validate
php lpwork health:check
```

Run migrations when switching to database-backed runtime storage such as database queues, sessions, locks, cache, scheduler history, or notifications.

## Port Customization

Most host ports can be customized with environment variables consumed by `.devcontainer/docker-compose.yml`.

| Variable | Default | Service port |
| --- | --- | --- |
| `LPWORK_DEBUGBAR_WS_HOST_PORT` | `8082` | PHP debugbar websocket `8081` |
| `LPWORK_POSTGRES_PORT` | `54320` | PostgreSQL `5432` |
| `LPWORK_MYSQL_PORT` | `33060` | MySQL `3306` |
| `LPWORK_REDIS_PORT` | `63790` | Redis `6379` |
| `LPWORK_ADMINER_PORT` | `18083` | Adminer `8080` |
| `LPWORK_REDIS_COMMANDER_PORT` | `18084` | Redis Commander `8081` |
| `LPWORK_MAILPIT_SMTP_PORT` | `1025` | Mailpit SMTP `1025` |
| `LPWORK_MAILPIT_HTTP_PORT` | `8025` | Mailpit UI `8025` |
| `LPWORK_MINIO_API_PORT` | `9000` | MinIO API `9000` |
| `LPWORK_MINIO_CONSOLE_PORT` | `9001` | MinIO console `9001` |
| `LPWORK_LOCALSTACK_PORT` | `4566` | LocalStack edge `4566` |
| `LPWORK_SOKETI_PORT` | `6001` | Soketi websocket `6001` |
| `LPWORK_SOKETI_METRICS_PORT` | `9601` | Soketi metrics `9601` |
| `LPWORK_STATSD_PORT` | `8125` | StatsD UDP `9125` |
| `LPWORK_STATSD_TCP_PORT` | `8125` | StatsD TCP `9125` |
| `LPWORK_STATSD_METRICS_PORT` | `9102` | StatsD metrics `9102` |

Set these variables in your host environment or a local Compose override before starting the devcontainer. Changing host ports only changes how your host reaches the service. It does not change the internal service hostname or port that application configuration should use inside the container.

The nginx host port is currently fixed at `8080` in the default compose file. Use a local Compose override if that port conflicts.

## Optional Worker Profiles

The default devcontainer starts infrastructure services, not long-running application workers.

Optional profiles:

| Profile | Service | Command |
| --- | --- | --- |
| `queue` | `queue-worker` | Repeatedly runs `./lpwork queue:work --connection=${LPWORK_QUEUE_WORKER_CONNECTION:-sync} --queue=${LPWORK_QUEUE_WORKER_QUEUE:-default} --max-jobs=1 --sleep=1`. |
| `scheduler` | `scheduler` | Runs `./lpwork schedule:run` every 60 seconds. |

Use these profiles only when you intentionally want background work running during local development.

## Service Customization

The devcontainer provides optional services so LPWork features can be enabled through application configuration. The framework should not assume these services are always present.

When customizing services:

- Prefer changing application `.env` values for driver selection, hostnames, credentials, buckets, queues, or transports.
- Prefer `LPWORK_*` host-port variables or local Compose overrides for port conflicts.
- Keep internal service names stable when application config points at them.
- Do not treat VS Code extension availability as a runtime dependency.
- Run `php lpwork health:check` after changing service wiring.
- Run `php lpwork about` when you need a quick orientation of active modules and drivers.

Do not rebuild the devcontainer as the first response to tooling drift. Prefer rerunning setup commands, installing missing packages in the current container, restarting one compose service, or recreating only the affected service. Rebuild only when the Dockerfile or base image change cannot be applied safely to the active container.
