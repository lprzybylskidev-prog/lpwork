<?php

declare(strict_types=1);

namespace App\Modules\Welcome\Routes;

use App\Modules\Welcome\Controllers\HomeController;
use LPWork\Routing\Contracts\RouteDefinition;
use LPWork\Routing\Router;

final class WebRoutes implements RouteDefinition
{
    public function register(Router $router): void
    {
        $router->get('/', [HomeController::class, 'index'])->name('home');
    }
}
