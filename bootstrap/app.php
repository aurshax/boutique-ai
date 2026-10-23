<?php

use App\Http\Middleware\EnsureAccountPagesEnabled;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrustConfiguredProxies;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Behind a hosting load balancer / reverse proxy: trust X-Forwarded-* from config('app.trusted_proxies')
        // (read per request, so it also works with `config:cache`, unlike env() here).
        $middleware->replace(TrustProxies::class, TrustConfiguredProxies::class);

        // Account pages off → 404 before `auth` can redirect a guest to the (hidden) login page.
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: EnsureAccountPagesEnabled::class,
        );

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecurityHeaders::class,   // CSP + security headers (the nonce is also added to @vite tags)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
