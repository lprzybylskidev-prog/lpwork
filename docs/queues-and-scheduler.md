# Queues And Scheduler

LPWork queues move work out of the current request or command. The scheduler declares recurring command and job execution. Both systems are configured services and should be operated through framework commands.

## Queue Configuration

`App/Shared/Configs/QueueConfig.php` selects the default queue connection from `QUEUE_CONNECTION` and the default queue name from `QUEUE_NAME`.

Supported built-in queue drivers are:

| Driver | Use |
| --- | --- |
| `sync` | Run jobs immediately in the current process. Useful for local development and tests. |
| `database` | Store jobs in a framework-managed database table. |
| `redis` | Store jobs in Redis. |
| `sqs` | Send jobs to AWS SQS or an SQS-compatible queue. |

Queue retry and retention settings are configured under `queue.retry` and `queue.retention`.

Use `LPWork\Queue\QueueManager` to dispatch jobs:

```php
use LPWork\Queue\QueueDispatchOptions;
use LPWork\Queue\QueueManager;

$id = $queue->dispatch(
    new SendWelcomeEmail($userId),
    new QueueDispatchOptions(
        connection: 'database',
        queue: 'mail',
        delaySeconds: 30,
        maxAttempts: 5,
    ),
);
```

Jobs must be serializable by the queue payload serializer. Keep job payloads explicit and small: scalar IDs, value objects, and data needed to reload state are usually better than embedding large runtime objects.

## Queue Workers

Queue workers reserve jobs from a connection and execute them through the application container.

Useful commands:

| Command | Use |
| --- | --- |
| `php lpwork queue:work` | Process queued jobs continuously. |
| `php lpwork queue:work --once` | Process one job and stop. |
| `php lpwork queue:work --connection=database --queue=mail` | Process a named queue connection and queue. |
| `php lpwork queue:work --max-jobs=100` | Stop after a maximum number of reserved jobs. |
| `php lpwork queue:clear --connection=database --queue=mail` | Clear pending and reserved jobs. Requires `--force` in production. |
| `php lpwork queue:prune --connection=database` | Prune retained completed and failed jobs. |

Worker options can override configured retry timing:

| Option | Meaning |
| --- | --- |
| `--sleep=VALUE` | Seconds to sleep when no job is available. |
| `--retry-after=VALUE` | Seconds before a reserved job may be retried. |
| `--delay=VALUE` | Seconds before a released job becomes available again. |

Run long-lived workers as an explicit deployment process. Do not start workers during service-provider registration or normal HTTP request handling.

## Scheduler Configuration

`App/Shared/Configs/ScheduleConfig.php` configures scheduler lock TTL, run storage, and run-history retention.

The scheduler stores run state in the configured database table and uses atomic locks to avoid overlapping tasks by default. Run `php lpwork migrate` after enabling scheduler support so the schedule runs table exists.

## Declaring Scheduled Tasks

Create an application schedule provider by extending `LPWork\Schedule\Providers\ScheduleProvider`, then register that provider with the application.

```php
use LPWork\Schedule\Providers\ScheduleProvider;
use LPWork\Schedule\ScheduleRegistry;

final class AppScheduleProvider extends ScheduleProvider
{
    protected function schedule(ScheduleRegistry $schedule): void
    {
        $schedule
            ->command('reports:send', options: ['quiet' => true], name: 'reports.send')
            ->hourly();

        $schedule
            ->job(new RebuildSearchIndex(), name: 'search.rebuild')
            ->dailyAt('02:15')
            ->onQueue(connection: 'database', queue: 'maintenance');
    }
}
```

Available frequency helpers are `cron`, `everyMinute`, `everyMinutes`, `hourly`, and `dailyAt`.

Scheduled tasks prevent overlapping by default. Call `allowOverlapping()` only when duplicate concurrent execution is safe. Call `delay(seconds: ...)` when queued scheduled jobs should become available later.

## Running The Scheduler

Useful commands:

| Command | Use |
| --- | --- |
| `php lpwork schedule:list` | List registered scheduled tasks. |
| `php lpwork schedule:run` | Run due scheduled tasks once. |
| `php lpwork schedule:run --task=reports.send` | Run one task by schedule name. |
| `php lpwork schedule:run --force` | Run tasks even when not due. |
| `php lpwork schedule:prune` | Prune expired locks and retained history. Requires `--force` in production. |

Use the host scheduler, supervisor, or container orchestration to call `php lpwork schedule:run` on the desired cadence. LPWork intentionally keeps scheduler execution as an explicit command boundary.

## Events And Observability

Queues dispatch events when jobs are queued and worker lifecycle changes occur. The scheduler dispatches task starting, succeeded, failed, and skipped events. Use listeners for optional logging, metrics, auditing, or follow-up behavior.

Queue and schedule activity can appear in debug context, health checks, and runtime diagnostics when the matching modules are enabled.
