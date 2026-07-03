<?php

declare(strict_types=1);

use LPWork\Cache\CacheStore;
use LPWork\Translation\JsonTranslationLoader;
use LPWork\Translation\Translator;
use LPWork\View\Exceptions\InvalidPhpViewExtensionException;
use LPWork\View\Exceptions\ViewNotFoundException;
use LPWork\View\PhpViewEngine;
use LPWork\View\PhpViewEngineExtensions;
use LPWork\View\ViewDebugCollector;
use LPWork\View\ViewFactory;
use LPWork\View\ViewFinder;
use LPWork\View\ViewNamespaceRegistry;
use Tests\support\view\TrackingCacheDriver;
use Tests\support\view\UppercaseViewEngine;
use Tests\support\view\ViewTestEnvironment;

it('renders PHP views with isolated scope and explicit data', function (): void {
    $environment = ViewTestEnvironment::create();

    try {
        $environment->createView('views/dashboard.php', '<?= $view->e($title) ?>|<?= isset($secret) ? "leaked" : "isolated" ?>');

        expect($environment->factory()->render('dashboard', ['title' => '<Dashboard>']))
            ->toBe('&lt;Dashboard&gt;|isolated');
    } finally {
        $environment->remove();
    }
});

it('collects rendered view debug context', function (): void {
    $environment = ViewTestEnvironment::create();

    try {
        $environment->createView('views/profile.php', 'Hello <?= $name ?>');
        $collector = new ViewDebugCollector();
        $factory = new ViewFactory(
            new ViewFinder(['views'], $environment->basePath()),
            new PhpViewEngine(),
            debugCollector: $collector,
        );
        $factory->share('app', 'LPWork');

        $factory->render('profile', ['name' => 'Ada']);

        expect($collector->renders())->toHaveCount(1)
            ->and($collector->renders()[0])->toMatchArray([
                'Name' => 'profile',
                'Path' => $environment->basePath() . '/views/profile.php',
                'Layout' => null,
                'Data keys' => ['app', 'name'],
                'Shared keys' => ['app'],
                'Sections' => [],
                'Successful' => true,
            ])
            ->and($collector->renders()[0]['Duration ms'])->toBeFloat();
    } finally {
        $environment->remove();
    }
});

it('renders PHP views with object view data exposed as data', function (): void {
    $environment = ViewTestEnvironment::create();

    try {
        $environment->createView('views/dashboard.php', '<?= $view->e($data->title) ?>|<?= isset($title) ? "leaked" : "object" ?>');

        $data = new class ('<Dashboard>') {
            public function __construct(
                public string $title,
            ) {}
        };

        expect($environment->factory()->render('dashboard', $data))
            ->toBe('&lt;Dashboard&gt;|object');
    } finally {
        $environment->remove();
    }
});

it('renders object view data with shared data', function (): void {
    $environment = ViewTestEnvironment::create();

    try {
        $environment->createView('views/dashboard.php', '<?= $view->e($appName) ?>:<?= $view->e($data->title) ?>');

        $data = new class ('Dashboard') {
            public function __construct(
                public string $title,
            ) {}
        };

        $factory = $environment->factory();
        $factory->share('appName', 'LPWork');

        expect($factory->render('dashboard', $data))->toBe('LPWork:Dashboard');
    } finally {
        $environment->remove();
    }
});

it('throws a domain exception when a view cannot be found', function (): void {
    $environment = ViewTestEnvironment::create();

    try {
        expect(fn(): string => $environment->factory()->render('missing'))
            ->toThrow(ViewNotFoundException::class);
    } finally {
        $environment->remove();
    }
});

it('renders partials with parent data and explicit overrides', function (): void {
    $environment = ViewTestEnvironment::create();

    try {
        $environment->createView('views/dashboard.php', 'Hello <?php $view->include("partials.name", ["name" => "Ada"]); ?>');
        $environment->createView('views/partials/name.php', '<?= $view->e($name) ?> from <?= $view->e($team) ?>');

        expect($environment->factory()->render('dashboard', ['name' => 'Grace', 'team' => 'Core']))
            ->toBe('Hello Ada from Core');
    } finally {
        $environment->remove();
    }
});

it('renders layouts with content and named sections', function (): void {
    $environment = ViewTestEnvironment::create();

    try {
        $environment->createView('views/page.php', <<<'PHP'
            <?php $view->layout('layouts.app', ['chrome' => 'default']); ?>
            <?php $view->start('title'); ?>Dashboard<?php $view->end(); ?>
            <main><?= $view->e($body) ?></main>
            PHP);
        $environment->createView('views/layouts/app.php', '<title><?= $view->section("title") ?></title><body data-chrome="<?= $view->e($chrome) ?>"><?= $view->content() ?></body>');

        expect($environment->factory()->render('page', ['body' => '<Ready>']))
            ->toBe('<title>Dashboard</title><body data-chrome="default"><main>&lt;Ready&gt;</main></body>');
    } finally {
        $environment->remove();
    }
});

it('merges shared data without overriding explicit render data', function (): void {
    $environment = ViewTestEnvironment::create();

    try {
        $environment->createView('views/profile.php', '<?= $view->e($appName) ?>:<?= $view->e($name) ?>');

        $factory = $environment->factory();
        $factory->share('appName', 'LPWork');
        $factory->share('name', 'Shared');

        expect($factory->render('profile', ['name' => 'Ada']))->toBe('LPWork:Ada');
    } finally {
        $environment->remove();
    }
});

it('caches resolved view paths', function (): void {
    $environment = ViewTestEnvironment::create();
    $driver = new TrackingCacheDriver();

    try {
        $environment->createView('views/cached.php', 'cached');
        $factory = $environment->factory(new CacheStore('views', $driver));

        expect($factory->render('cached'))->toBe('cached')
            ->and($factory->render('cached'))->toBe('cached')
            ->and($driver->gets)->toBe(2)
            ->and($driver->puts)->toBe(1);
    } finally {
        $environment->remove();
    }
});

it('recovers from stale cached view paths by resolving the view again', function (): void {
    $environment = ViewTestEnvironment::create();
    $driver = new TrackingCacheDriver();

    try {
        $environment->createView('views/fresh.php', 'fresh');
        $factory = $environment->factory(new CacheStore('views', $driver));
        $driver->put('view.path.' . hash('sha256', 'fresh|fresh.php'), $environment->basePath() . '/views/missing.php');

        expect($factory->render('fresh'))->toBe('fresh')
            ->and($driver->puts)->toBe(2);
    } finally {
        $environment->remove();
    }
});

it('renders namespaced views from registered module paths', function (): void {
    $environment = ViewTestEnvironment::create();
    $namespaces = new ViewNamespaceRegistry();
    $namespaces->add('welcome', 'modules/welcome/views');

    try {
        $environment->createView('modules/welcome/views/home.php', 'Welcome <?= $view->e($name) ?>');

        $factory = new ViewFactory(
            new ViewFinder(['views'], $environment->basePath(), namespaces: $namespaces),
            new PhpViewEngine(),
        );

        expect($factory->render('welcome::home', ['name' => 'Ada']))->toBe('Welcome Ada');
    } finally {
        $environment->remove();
    }
});

it('renders through a replacement view engine contract', function (): void {
    $environment = ViewTestEnvironment::create();

    try {
        $environment->createView('views/custom.php', 'ignored');

        expect($environment->factory(engine: new UppercaseViewEngine())->render('custom', ['message' => 'custom engine']))
            ->toBe('CUSTOM ENGINE');
    } finally {
        $environment->remove();
    }
});

it('renders PHP views with engine globals and functions', function (): void {
    $environment = ViewTestEnvironment::create();
    $extensions = new PhpViewEngineExtensions();
    $extensions->global('appName', 'LPWork');
    $extensions->function('asset', static fn(string $path): string => '/assets/' . ltrim($path, '/'));

    try {
        $environment->createView('views/profile.php', '<?= $view->e($appName) ?>|<?= $view->e($asset("app.css")) ?>|<?= $view->e($name) ?>');

        expect($environment->factory(engine: new LPWork\View\PhpViewEngine(extensions: $extensions))->render('profile', ['name' => 'Ada']))
            ->toBe('LPWork|/assets/app.css|Ada');
    } finally {
        $environment->remove();
    }
});

it('lets explicit render data override PHP view engine globals', function (): void {
    $environment = ViewTestEnvironment::create();
    $extensions = new PhpViewEngineExtensions();
    $extensions->global('name', 'Shared');

    try {
        $environment->createView('views/profile.php', '<?= $view->e($name) ?>');

        expect($environment->factory(engine: new LPWork\View\PhpViewEngine(extensions: $extensions))->render('profile', ['name' => 'Ada']))
            ->toBe('Ada');
    } finally {
        $environment->remove();
    }
});

it('rejects invalid PHP view extension names', function (): void {
    $extensions = new PhpViewEngineExtensions();

    expect(fn() => $extensions->global('not-valid', 'value'))
        ->toThrow(InvalidPhpViewExtensionException::class)
        ->and(fn() => $extensions->function('view', static fn(): string => 'reserved'))
        ->toThrow(InvalidPhpViewExtensionException::class);
});

it('translates keys and text from PHP views', function (): void {
    $environment = ViewTestEnvironment::create();

    try {
        $environment->createView('views/profile.php', '<?= $view->t("validation.required", ["field" => "email"]) ?>|<?= $view->text("Save") ?>');
        $environment->createView('pl_PL.json', '{"validation.required":"Pole :field jest wymagane.","Save":"Zapisz"}');

        $translator = new Translator(new JsonTranslationLoader($environment->basePath()), locale: 'pl_PL');

        expect($environment->factory(translator: $translator)->render('profile'))
            ->toBe('Pole email jest wymagane.|Zapisz');
    } finally {
        $environment->remove();
    }
});
