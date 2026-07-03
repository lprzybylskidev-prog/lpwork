# Mail, Notifications, And Broadcasting

LPWork separates direct mail delivery, multi-channel notifications, and realtime-style broadcasting. Configure transports and channels first, then resolve the runtime manager through the container.

## Mail

`App/Shared/Configs/MailConfig.php` selects the default transport from `MAIL_TRANSPORT` and the default sender from `MAIL_FROM`.

Supported built-in mail transports are:

| Transport | Use |
| --- | --- |
| `log` | Record mail without delivering it. Useful for local development. |
| `smtp` | Send through SMTP, including local Mailpit. |
| `sendmail` | Execute a local sendmail-compatible command. |
| `ses` | Send through AWS SES-compatible credentials. |
| `mailgun` | Send through Mailgun. |

Use `LPWork\Mail\MailManager` to build and send messages:

```php
use LPWork\Mail\MailManager;

$message = $mail->message()
    ->to('ada@example.test', 'Ada')
    ->subject('Welcome to LPWork')
    ->text('Your account is ready.');

$result = $mail->send($message);
```

If a message does not define `from`, LPWork applies the configured default sender. A sendable message must have at least one recipient, a sender, a subject, and either text or HTML body content.

Optional mail logging records outbound metadata. In debug mode it may include subject and addresses; avoid enabling overly detailed logging for sensitive production mail.

## Notifications

Notifications wrap a message that may be delivered through one or more channels. Built-in notification channels include:

| Channel | Use |
| --- | --- |
| `mail` | Send through the configured mail manager. |
| `database` | Store notification records in a database table. |
| `broadcast` | Broadcast notification payloads through the broadcast manager. |

A notifiable object provides notification routes. A notification chooses channels for that notifiable.

```php
use LPWork\Notifications\Contracts\Notifiable;
use LPWork\Notifications\NotificationRoutes;

final readonly class AccountNotifiable implements Notifiable
{
    public function __construct(private string $email) {}

    public function notificationRoutes(): NotificationRoutes
    {
        return NotificationRoutes::create()->mail($this->email);
    }
}
```

Send through `LPWork\Notifications\NotificationManager`:

```php
$notifications->send(new AccountNotifiable($email), new AccountActivated());
```

Notifications that implement the queue contract can be queued when the notifiable supports queued payloads and the queue service is available. Keep queued notification payloads explicit and reloadable, just like normal queued jobs.

Notification lifecycle events are emitted for sending, sent, failed, and queued states. Use listeners for optional auditing, metrics, or follow-up work.

## Broadcasting

`App/Shared/Configs/BroadcastingConfig.php` selects the default broadcaster from `BROADCAST_CONNECTION`.

Supported built-in broadcasters are:

| Broadcaster | Use |
| --- | --- |
| `none` | Discard broadcasts. |
| `log` | Record broadcast payloads for local diagnostics. |
| `sync` | Process-local broadcasts for tests and synchronous flows. |
| `redis` | Publish broadcasts through Redis. |
| `pusher` | Publish broadcasts through a Pusher-compatible service. |

Broadcast a message directly:

```php
use LPWork\Broadcasting\BroadcastMessage;

$broadcasts->broadcast(new BroadcastMessage(
    channels: ['orders.42'],
    name: 'order.updated',
    payload: ['status' => 'paid'],
));
```

Or implement `LPWork\Broadcasting\Contracts\BroadcastableEvent` on an event object and pass it to the broadcast manager.

Broadcasting dispatches sending, sent, and failed events. Keep payloads intentionally shaped and avoid broadcasting secrets, tokens, private email addresses, or internal diagnostics unless the channel is explicitly private and authorized by the application.

## Runtime Setup

- Configure external credentials in environment values, not in module code.
- Use `log`, `none`, or local development transports until external providers are intentionally enabled.
- Run `php lpwork health:check` after changing external mail, notification, broadcast, queue, or database configuration.
- Add module tests around message construction, selected channels, and queued payloads. External providers should be covered through fakes, local services, or focused integration checks rather than live production credentials.
