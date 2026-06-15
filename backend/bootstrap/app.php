<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Supabase JWT auth guard for the API. Routes reference this as
        // ->middleware('supabase'). It does NOT use Laravel's session/auth
        // guards — it verifies the Supabase bearer token and resolves the user.
        $middleware->alias([
            'supabase' => \App\Http\Middleware\SupabaseAuth::class,
        ]);
        // NOTE: auth is pure bearer-token (Supabase JWT) — NOT Sanctum cookie
        // auth — so we intentionally do not call statefulApi().
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Always return JSON for API routes instead of HTML error pages.
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
