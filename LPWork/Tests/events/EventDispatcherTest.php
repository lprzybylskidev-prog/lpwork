<?php

declare(strict_types=1);

use LPWork\Container\Container;
use LPWork\ErrorHandling\HttpDebugContext;
use LPWork\Events\EventDebugCollector;
use LPWork\Events\EventDispatcher;
use LPWork\Events\EventRegistry;
use LPWork\Events\Exceptions\DuplicateListenerException;
use LPWork\Events\Exceptions\InvalidListenerException;
use LPWork\Events\Providers\EventServiceProvider;
use LPWork\Events\Providers\EventsProvider;
use LPWork\Foundation\Contracts\ServiceProvider;
use Tests\support\events\EventLog;
use Tests\support\events\FirstListener;
use Tests\support\events\InvalidListener;
use Tests\support\events\SecondListener;
use Tests\support\events\TestEvent;
use Tests\support\events\ThrowingListener;

it('defines the event service provider', function (): void {
    expect(new EventServiceProvider())->toBeInstanceOf(ServiceProvider::class);
});

it('registers listeners explicitly through providers', function (): void {
    $container = new Container();
    $container->singleton(EventRegistry::class);
    $provider = new class extends EventsProvider {
        protected function listeners(): array
        {
            return [
                TestEvent::class => [
                    FirstListener::class,
                    SecondListener::class,
                ],
            ];
        }
    };

    $provider->register($container);
    $registry = $container->make(EventRegistry::class);

    expect($registry)->toBeInstanceOf(EventRegistry::class);

    if (!$registry instanceof EventRegistry) {
        return;
    }

    expect($registry->listenersFor(new TestEvent()))->toBe([
        FirstListener::class,
        SecondListener::class,
    ]);
});

it('rejects duplicate listeners for the same event', function (): void {
    $registry = new EventRegistry();
    $registry->add(TestEvent::class, [FirstListener::class]);

    expect(fn() => $registry->add(TestEvent::class, [FirstListener::class]))
        ->toThrow(DuplicateListenerException::class);
});

it('dispatches listeners in registration order through the container', function (): void {
    $container = new Container();
    $log = new EventLog();
    $container->instance(EventLog::class, $log);
    new EventServiceProvider()->register($container);
    $registry = $container->make(EventRegistry::class);

    expect($registry)->toBeInstanceOf(EventRegistry::class);

    if (!$registry instanceof EventRegistry) {
        return;
    }

    $registry->add(TestEvent::class, [
        FirstListener::class,
        SecondListener::class,
    ]);

    $dispatcher = $container->make(EventDispatcher::class);

    expect($dispatcher)->toBeInstanceOf(EventDispatcher::class);

    if (!$dispatcher instanceof EventDispatcher) {
        return;
    }

    $dispatcher->dispatch(new TestEvent('alpha'));

    expect($log->entries())->toBe(['first:alpha', 'second:alpha']);
});

it('dispatches closure listeners when registered explicitly', function (): void {
    $container = new Container();
    $log = new EventLog();
    new EventServiceProvider()->register($container);
    $registry = $container->make(EventRegistry::class);

    expect($registry)->toBeInstanceOf(EventRegistry::class);

    if (!$registry instanceof EventRegistry) {
        return;
    }

    $registry->add(TestEvent::class, [
        static function (object $event) use ($log): void {
            if ($event instanceof TestEvent) {
                $log->add('closure:' . $event->name);
            }
        },
    ]);
    $dispatcher = $container->make(EventDispatcher::class);

    if (!$dispatcher instanceof EventDispatcher) {
        return;
    }

    $dispatcher->dispatch(new TestEvent('beta'));

    expect($log->entries())->toBe(['closure:beta']);
});

it('propagates listener exceptions', function (): void {
    $container = new Container();
    new EventServiceProvider()->register($container);
    $registry = $container->make(EventRegistry::class);

    if (!$registry instanceof EventRegistry) {
        return;
    }

    $registry->add(TestEvent::class, [ThrowingListener::class]);
    $dispatcher = $container->make(EventDispatcher::class);

    if (!$dispatcher instanceof EventDispatcher) {
        return;
    }

    expect(fn() => $dispatcher->dispatch(new TestEvent()))
        ->toThrow(RuntimeException::class, 'Listener failed.');
});

it('rejects listener classes without handle methods', function (): void {
    $container = new Container();
    new EventServiceProvider()->register($container);
    $registry = $container->make(EventRegistry::class);

    if (!$registry instanceof EventRegistry) {
        return;
    }

    $registry->add(TestEvent::class, [InvalidListener::class]);
    $dispatcher = $container->make(EventDispatcher::class);

    if (!$dispatcher instanceof EventDispatcher) {
        return;
    }

    expect(fn() => $dispatcher->dispatch(new TestEvent()))
        ->toThrow(InvalidListenerException::class);
});

it('collects dispatched event and listener metadata for debug context', function (): void {
    $container = new Container();
    $context = new HttpDebugContext();
    $container->instance(HttpDebugContext::class, $context);
    new EventServiceProvider()->register($container);
    $registry = $container->make(EventRegistry::class);

    if (!$registry instanceof EventRegistry) {
        return;
    }

    $registry->add(TestEvent::class, [FirstListener::class]);
    $container->instance(EventLog::class, new EventLog());
    $dispatcher = $container->make(EventDispatcher::class);

    if (!$dispatcher instanceof EventDispatcher) {
        return;
    }

    $dispatcher->dispatch(new TestEvent('debug'));

    $events = $context->data()['Events'] ?? null;

    expect($container->make(EventDebugCollector::class))->toBeInstanceOf(EventDebugCollector::class)
        ->and($events)->toBeArray();

    if (!is_array($events) || !isset($events[0]) || !is_array($events[0])) {
        return;
    }

    expect($events[0]['event'])->toBe(TestEvent::class)
        ->and($events[0]['listeners'])->toBe([FirstListener::class])
        ->and($events[0]['Duration ms'])->toBeFloat()
        ->and($events[0]['Successful'])->toBeTrue();
});
