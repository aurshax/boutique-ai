<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers and a strict Content-Security-Policy.
 *
 * Why it matters: the customer's OpenAI key lives in the browser (sessionStorage).
 * Any XSS would leak it. The CSP blocks foreign and inline scripts:
 * only scripts from our origin or carrying the per-request nonce may run.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // A fresh nonce per request; @vite tags and the inline script in app.blade.php receive it.
        Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->policy(Vite::cspNonce()));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        return $response;
    }

    private function policy(?string $nonce): string
    {
        $dev = $this->viteDevServer();                 // 'http://127.0.0.1:5173' or null
        $devWs = $dev !== null ? preg_replace('#^http#', 'ws', $dev) : null;

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => array_filter(["'self'", $nonce !== null ? "'nonce-{$nonce}'" : null, $dev]),
            // Vue/Tailwind (dev) inject <style> tags from JS — so 'unsafe-inline' is allowed for styles only.
            'style-src' => array_filter(["'self'", "'unsafe-inline'", 'https://fonts.bunny.net', $dev]),
            'font-src' => array_filter(["'self'", 'data:', 'https://fonts.bunny.net', $dev]),
            'img-src' => ["'self'", 'data:', 'blob:'],
            'media-src' => ["'self'"],
            'connect-src' => array_filter(["'self'", $dev, $devWs]),   // the server talks to OpenAI, never the browser
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        return collect($directives)
            ->map(fn (array $sources, string $name) => $name.' '.implode(' ', $sources))
            ->implode('; ');
    }

    /** While `npm run dev` runs, public/hot holds the Vite dev server URL (local environment only). */
    private function viteDevServer(): ?string
    {
        if (! app()->isLocal() || ! Vite::isRunningHot()) {
            return null;
        }

        $url = trim((string) @file_get_contents(Vite::hotFile()));

        return preg_match('#^https?://[^/\s]+$#', $url) ? $url : null;
    }
}
