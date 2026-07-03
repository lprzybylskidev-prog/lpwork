<?php

declare(strict_types=1);

namespace Tests\support\container;

final class ActionController
{
    public function show(SimpleService $service, string $id): string
    {
        return $service::class . ':' . $id;
    }
}
