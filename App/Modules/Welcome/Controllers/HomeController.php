<?php

declare(strict_types=1);

namespace App\Modules\Welcome\Controllers;

use LPWork\Foundation\FrameworkMetadata;
use LPWork\Foundation\FrameworkModuleCatalog;
use LPWork\Http\ViewRenderer;
use LPWork\Responses\HttpResponse;

final readonly class HomeController
{
    public function __construct(
        private FrameworkModuleCatalog $modules,
        private FrameworkMetadata $metadata,
    ) {}

    public function index(ViewRenderer $views): HttpResponse
    {
        return $views->render('welcome::home', [
            'frameworkVersion' => $this->metadata->version(),
            'moduleCount' => $this->modules->count(),
            'modules' => $this->modules->all(),
        ]);
    }
}
