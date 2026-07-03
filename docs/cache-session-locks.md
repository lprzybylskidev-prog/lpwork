# Cache, Session, And Locks

LPWork exposes cache, session, and atomic locking through configured runtime services. These services should be selected through configuration and resolved through the container, not constructed inside module code.

## Cache

`App/Shared/Configs/CacheConfig.php` selects the default store from `CACHE_STORE`.

Supported built-in cache drivers are:

| Driver | Use |
| --- | --- |
| `file` | Local filesystem cache backed by a configured storage disk/path. |
| `redis` | Shared cache through Redis. |
| `database` | Shared cache through a framework-managed database table. |
| `apcu` | In-process cache through APCu. |

Resolve `LPWork\Cache\CacheStore` for the default store or `LPWork\Cache\CacheManager` when choosing a named store.

```php
use LPWork\Cache\CacheStore;

final readonly class ProductLookup
{
    public function __construct(private CacheStore $cache) {}

    public function rememberName(int $id, callable $loader): string
    {
        $key = "products.{$id}.name";
        $cached = $this->cache->get($key);

        if (is_string($cached)) {
            return $cached;
        }

        $name = (string) $loader();
        $this->cache->put($key, $name, ttlSeconds: 3600);

        return $name;
    }
}
```

`CacheStore` supports `get`, `put`, `add`, `forget`, `forgetIfValue`, and `clear`.

Use `add` when a value should only be stored if the key is missing. Use `forgetIfValue` for compare-and-delete flows, such as lock and token cleanup.

Cache maintenance commands:

| Command | Use |
| --- | --- |
| `php lpwork cache:clear` | Clear framework cache stores. Requires `--force` in production. |
| `php lpwork cache:clear views` | Clear one cache target, such as compiled views. |
| `php lpwork cache:rebuild` | Rebuild compiled framework caches. |
| `php lpwork cache:rebuild --only=routes --only=translations` | Rebuild selected cache targets. |

## Session

`App/Shared/Configs/SessionConfig.php` selects the default session driver from `SESSION_DRIVER`.

Supported built-in session drivers are:

| Driver | Use |
| --- | --- |
| `php` | Native PHP session storage behind the framework boundary. |
| `memory` | Process-local sessions for tests and non-persistent flows. |
| `cache` | Session state stored in a configured cache store. |
| `database` | Session state stored in a framework-managed database table. |
| `redis` | Session state stored in Redis. |

Resolve `LPWork\Session\SessionManager` when choosing a named driver. HTTP session middleware and request handling should use the configured framework session boundary rather than direct `$_SESSION` access.

Do not call PHP session functions from module internals. Session lifecycle belongs at the framework boundary so tests, repeated bootstrap, CLI flows, and HTTP middleware can control it safely.

## Atomic Locks

`App/Shared/Configs/LockConfig.php` selects the backing lock store and default TTL.

Supported built-in lock stores are:

| Store | Use |
| --- | --- |
| `cache` | Locks backed by the configured cache store. |
| `database` | Locks backed by a framework-managed database table. |
| `redis` | Locks backed by Redis. |

Resolve `LPWork\Locks\AtomicLockManager` and request a named lock.

```php
use LPWork\Locks\AtomicLockManager;

$lock = $locks->lock('reports:monthly', ttlSeconds: 300);

if (!$lock->acquire()) {
    return;
}

try {
    $this->buildReport();
} finally {
    $lock->release();
}
```

Use locks for scheduled work, expensive rebuilds, idempotent exports, and short critical sections. Keep lock names stable and specific to the protected resource. Always release acquired locks in a `finally` block unless the driver method being used owns the release lifecycle.

## Runtime Tables

Database-backed cache, session, and lock stores register their framework migrations through the owning framework module. Run `php lpwork migrate` after enabling a database-backed driver so support tables exist before runtime traffic needs them.
