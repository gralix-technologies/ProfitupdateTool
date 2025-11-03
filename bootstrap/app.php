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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);
        
        $middleware->api(append: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\SecurityValidationMiddleware::class,
        ]);
        
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'security.validation' => \App\Http\Middleware\SecurityValidationMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
    })->create();



