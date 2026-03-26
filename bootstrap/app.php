<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'sistemas_admin' => \App\Http\Middleware\SistemasAdminMiddleware::class,
            'area.rh' => \App\Http\Middleware\AreaRHMiddleware::class,
            'area.logistica' => \App\Http\Middleware\AreaLogisticaMiddleware::class,
            'area.legal' => \App\Http\Middleware\AreaLegalMiddleware::class,
            'api.key' => \App\Http\Middleware\CheckApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
