<?php

declare(strict_types=1);

use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Translation\Commands\TranslationCacheCommand;
use LPWork\Translation\Commands\TranslationClearCommand;
use LPWork\Translation\Exceptions\InvalidTranslationFileException;
use LPWork\Translation\JsonTranslationLoader;
use LPWork\Translation\TranslationCache;
use LPWork\Translation\TranslationCompiledCache;
use LPWork\Translation\TranslationNamespaceRegistry;
use LPWork\Translation\Translator;
use Tests\support\ApplicationTestEnvironment;
use Tests\support\console\OutputStreams;

afterAll(function (): void {
    ApplicationTestEnvironment::removeDirectories();
});

it('translates keys with parameters for the configured locale', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('lang/pl_PL.json', '{"validation.required":"Pole :field jest wymagane."}');

    $translator = new Translator(new JsonTranslationLoader($environment->basePath() . '/lang'), locale: 'pl_PL');

    expect($translator->get('validation.required', ['field' => 'email']))
        ->toBe('Pole email jest wymagane.');
});

it('formats translated parameters with placeholder modifiers', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('lang/pl_PL.json', json_encode([
        'validation.required' => 'Pole :Field jest wymagane.',
        'validation.upper' => 'Pole :field:upper jest wymagane.',
        'validation.lower' => 'Pole :field:lower jest wymagane.',
        'validation.title' => 'Pole :field:title jest wymagane.',
        'validation.attributes.password' => 'hasło',
    ], JSON_THROW_ON_ERROR));

    $translator = new Translator(new JsonTranslationLoader($environment->basePath() . '/lang'), locale: 'pl_PL');

    expect($translator->get('validation.required', ['field' => 'password']))->toBe('Pole Hasło jest wymagane.')
        ->and($translator->get('validation.upper', ['field' => 'password']))->toBe('Pole HASŁO jest wymagane.')
        ->and($translator->get('validation.lower', ['field' => 'PASSWORD']))->toBe('Pole password jest wymagane.')
        ->and($translator->get('validation.title', ['field' => 'user password']))->toBe('Pole User Password jest wymagane.');
});

it('falls back to english translated parameter values before using the raw value', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('lang/en_US.json', '{"validation.required":"The :Field field is required.","validation.attributes.password":"password"}');
    $environment->writeFile('lang/pl_PL.json', '{"validation.required":"Pole :field jest wymagane."}');

    $translator = new Translator(new JsonTranslationLoader($environment->basePath() . '/lang'), locale: 'pl_PL');

    expect($translator->get('validation.required', ['field' => 'password']))->toBe('Pole password jest wymagane.')
        ->and($translator->get('validation.required', ['field' => 'email']))->toBe('Pole email jest wymagane.');
});

it('falls back to english translations before returning the original key', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('lang/en_US.json', '{"validation.email":"The :field field must be valid."}');
    $environment->writeFile('lang/pl_PL.json', '{}');

    $translator = new Translator(new JsonTranslationLoader($environment->basePath() . '/lang'), locale: 'pl_PL');

    expect($translator->get('validation.email', ['field' => 'email']))->toBe('The email field must be valid.')
        ->and($translator->get('validation.missing'))->toBe('validation.missing');
});

it('translates literal text and supports explicit locale overrides', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('lang/en_US.json', '{"Save":"Save"}');
    $environment->writeFile('lang/pl_PL.json', '{"Save":"Zapisz"}');

    $translator = new Translator(new JsonTranslationLoader($environment->basePath() . '/lang'), locale: 'en_US');

    expect($translator->text('Save', locale: 'pl_PL'))->toBe('Zapisz');

    $translator->setLocale('pl_PL');

    expect($translator->text('Save'))->toBe('Zapisz');
});

it('translates namespaced module keys from registered translation paths', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('lang/pl_PL.json', '{"Save":"Zapisz"}');
    $environment->writeFile('App/Modules/Welcome/lang/pl_PL.json', '{"title":"Witaj"}');
    $namespaces = new TranslationNamespaceRegistry();
    $namespaces->add('welcome', $environment->basePath() . '/App/Modules/Welcome/lang');

    $translator = new Translator(
        new JsonTranslationLoader($environment->basePath() . '/lang', namespaces: $namespaces),
        locale: 'pl_PL',
    );

    expect($translator->get('welcome::title'))->toBe('Witaj')
        ->and($translator->text('Save'))->toBe('Zapisz');
});

it('rejects invalid translation files explicitly', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('lang/pl_PL.json', '{"validation.required": true}');

    $translator = new Translator(new JsonTranslationLoader($environment->basePath() . '/lang'), locale: 'pl_PL');

    expect(fn(): string => $translator->get('validation.required'))
        ->toThrow(InvalidTranslationFileException::class);
});

it('loads translations from compiled translation cache when it exists', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('lang/pl_PL.json', '{"Save":"Zapisz"}');
    $cache = new TranslationCache($environment->basePath(), translationPath: 'lang', path: 'cache/translations.php');

    $cache->write();
    $environment->writeFile('lang/pl_PL.json', '{"Save":"Zmienione"}');

    $translator = new Translator(
        new JsonTranslationLoader($environment->basePath() . '/lang', $cache),
        locale: 'pl_PL',
    );

    expect($translator->text('Save'))->toBe('Zapisz');
});

it('reports corrupted compiled translation cache files explicitly', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('cache/translations.php', <<<'PHP'
        <?php

        declare(strict_types=1);

        return ['pl_PL' => ['Save' => ['nested']]];
        PHP);

    $cache = new TranslationCache($environment->basePath(), translationPath: 'lang', path: 'cache/translations.php');
    $translator = new Translator(
        new JsonTranslationLoader($environment->basePath() . '/lang', $cache),
        locale: 'pl_PL',
    );

    expect(fn(): string => $translator->text('Save'))
        ->toThrow(InvalidTranslationFileException::class, $environment->basePath() . '/cache/translations.php');
});

it('loads namespaced module translations from compiled translation cache', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('lang/pl_PL.json', '{"Save":"Zapisz"}');
    $environment->writeFile('App/Modules/Welcome/lang/pl_PL.json', '{"title":"Witaj"}');
    $namespaces = new TranslationNamespaceRegistry();
    $namespaces->add('welcome', $environment->basePath() . '/App/Modules/Welcome/lang');
    $cache = new TranslationCache(
        $environment->basePath(),
        translationPath: 'lang',
        path: 'cache/translations.php',
        namespaces: $namespaces,
    );

    $cache->write();
    $environment->writeFile('App/Modules/Welcome/lang/pl_PL.json', '{"title":"Zmienione"}');

    $translator = new Translator(
        new JsonTranslationLoader($environment->basePath() . '/lang', $cache, $namespaces),
        locale: 'pl_PL',
    );

    expect($translator->get('welcome::title'))->toBe('Witaj');
});

it('rebuilds and clears translation cache through commands', function (): void {
    $environment = ApplicationTestEnvironment::create();
    $environment->writeFile('lang/pl_PL.json', '{"Save":"Zapisz"}');
    $cache = new TranslationCache($environment->basePath(), translationPath: 'lang', path: 'cache/translations.php');
    $streams = OutputStreams::create();

    expect(new TranslationCacheCommand(new TranslationCompiledCache($cache))->handle(
        new Input(['lpwork', 'translation:cache']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    ))->toBe(0)
        ->and($environment->basePath() . '/cache/translations.php')->toBeFile()
        ->and($streams->stdout())->toContain('Translation cache rebuilt successfully.');

    expect(new TranslationClearCommand($cache)->handle(
        new Input(['lpwork', 'translation:clear']),
        new Output($streams->stdout, $streams->stderr, decorated: false),
    ))->toBe(0)
        ->and(is_file($environment->basePath() . '/cache/translations.php'))->toBeFalse()
        ->and($streams->stdout())->toContain('Translation cache cleared successfully.');
});
