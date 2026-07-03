<?php

declare(strict_types=1);

use LPWork\Console\Commands\FrontendEntrypointsCommand;
use LPWork\Console\Input;
use LPWork\Console\Output;
use LPWork\Frontend\AssetEntryRegistry;
use LPWork\Frontend\ViteEntrypointResolver;
use Tests\support\console\OutputStreams;

it('resolves declared asset entries into Vite build inputs', function (): void {
    $entries = new AssetEntryRegistry();
    $entries->add('welcome::app', 'App/Modules/Welcome/resources/frontend/app.ts');
    $entries->add('admin-panel::app', 'App/Modules/AdminPanel/resources/frontend/app.ts');

    $resolver = new ViteEntrypointResolver($entries);

    expect($resolver->buildInputs())->toBe([
        'welcome/app' => 'App/Modules/Welcome/resources/frontend/app.ts',
        'admin-panel/app' => 'App/Modules/AdminPanel/resources/frontend/app.ts',
    ]);
});

it('renders Vite build inputs as JSON for the Vite config bridge', function (): void {
    $entries = new AssetEntryRegistry();
    $entries->add('welcome::app', 'App/Modules/Welcome/resources/frontend/app.ts');
    $streams = OutputStreams::create();
    $command = new FrontendEntrypointsCommand(new ViteEntrypointResolver($entries));

    expect($command->handle(new Input(['lpwork', 'frontend:entries']), new Output($streams->stdout, $streams->stderr, decorated: false)))->toBe(0)
        ->and(json_decode($streams->stdout(), associative: true, flags: JSON_THROW_ON_ERROR))->toBe([
            'welcome/app' => 'App/Modules/Welcome/resources/frontend/app.ts',
        ]);
});
