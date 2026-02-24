<?php

use App\Http\Middleware\RoleMiddleware;
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
        // Register buddy programme middleware aliases
        $middleware->alias([
            'buddy.participant' => \App\Http\Middleware\EnsureBuddyParticipant::class,
            'buddy.match' => \App\Http\Middleware\EnsureBuddyMatchAccess::class,
            'buddy.admin' => \App\Http\Middleware\EnsureBuddyAdmin::class,
            'role' => RoleMiddleware::class,
        ]);

        // // Exclude API routes from CSRF verification
        // $middleware->validateCsrfTokens(except: [
        //     'api/buddy/*',
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
