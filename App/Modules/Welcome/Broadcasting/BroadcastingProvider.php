<?php

declare(strict_types=1);

namespace App\Modules\Welcome\Broadcasting;

use LPWork\Broadcasting\BroadcastChannelRegistry;
use LPWork\Broadcasting\Providers\BroadcastChannelsProvider;

final class BroadcastingProvider extends BroadcastChannelsProvider
{
    protected function channels(BroadcastChannelRegistry $channels): void
    {
        $channels->public('public');
    }
}
