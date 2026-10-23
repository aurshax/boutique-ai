<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hides the starter-kit account area (login, sign-up, password reset, 2FA, passkeys, dashboard,
 * settings) when config('auth.enabled') is false — the public demo needs no accounts.
 *
 * A middleware rather than unregistering the routes: the frontend imports Wayfinder helpers for
 * these routes, so they must exist when the production assets are built.
 */
class EnsureAccountPagesEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('auth.enabled'), 404);

        return $next($request);
    }
}
