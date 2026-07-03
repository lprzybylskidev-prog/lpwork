<?php

declare(strict_types=1);

namespace App\Modules\Welcome\View;

use LPWork\View\Providers\PhpViewEngineProvider;

final class ViewProvider extends PhpViewEngineProvider
{
    /**
     * @return array<string, string>
     */
    protected function viewNamespaces(): array
    {
        return [
            'welcome' => 'App/Modules/Welcome/resources/views',
        ];
    }
}
