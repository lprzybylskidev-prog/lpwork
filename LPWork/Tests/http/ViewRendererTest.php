<?php

declare(strict_types=1);

use LPWork\Http\ViewRenderer;
use LPWork\View\PhpViewEngine;
use LPWork\View\ViewFactory;
use LPWork\View\ViewFinder;

it('renders named views as HTML responses', function (): void {
    $basePath = sys_get_temp_dir() . '/lpwork_http_views_' . uniqid('', true);

    if (!mkdir($basePath . '/views', recursive: true)) {
        throw new RuntimeException('Could not create temporary view directory.');
    }

    try {
        file_put_contents($basePath . '/views/dashboard.php', '<h1><?= $view->e($title) ?></h1>');

        $factory = new ViewFactory(new ViewFinder(['views'], $basePath), new PhpViewEngine());
        $response = new ViewRenderer($factory)->render('dashboard', ['title' => 'Dashboard'], statusCode: 201);

        expect($response->statusCode())->toBe(201)
            ->and($response->header('Content-Type'))->toBe('text/html; charset=UTF-8')
            ->and($response->body())->toBe('<h1>Dashboard</h1>');
    } finally {
        unlink($basePath . '/views/dashboard.php');
        rmdir($basePath . '/views');
        rmdir($basePath);
    }
});
