<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Registar os aliases para usar de forma limpa no ficheiro de rotas
        $middleware->alias([
            'custom.auth' => \App\Http\Middleware\CustomAuthMiddleware::class,
            'role'        => \App\Http\Middleware\RoleMiddleware::class,
            'rate.limit'  => \App\Http\Middleware\RateLimitMiddleware::class,
        ]);
        
        // Adicionar middleware global para proteger contra CSRF em formulários web
        $middleware->validateCsrfTokens([
            'photo' => 'POST,PATCH,PUT,DELETE',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
