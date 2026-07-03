<?php

declare(strict_types=1);

namespace App\Modules\Welcome\Assets;

use LPWork\Frontend\Providers\AssetEntrypointsProvider;

final class AssetsProvider extends AssetEntrypointsProvider
{
    /**
     * @return array<string, string>
     */
    protected function assetEntries(): array
    {
        return [
            'welcome::app' => 'App/Modules/Welcome/resources/frontend/app.ts',
        ];
    }
}
