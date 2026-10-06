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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => '/'.config('portfolio.admin.path'));
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        // Désinscription en un clic depuis Gmail / Outlook (POST sans jeton CSRF, protégée par la signature du lien).
        $middleware->validateCsrfTokens(except: ['newsletter/desinscription/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
