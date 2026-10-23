<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Xavfsizlik sarlavhalari va qat'iy Content-Security-Policy.
 *
 * Nima uchun muhim: mijozning OpenAI kaliti brauzerda (sessionStorage) turadi.
 * XSS bo'lsa — kalit o'g'irlanadi. CSP begona va inline skriptlarni bloklaydi:
 * faqat bizning serverdan va nonce bilan belgilangan skriptlar ishlaydi.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Har so'rov uchun yangi nonce; @vite teglari va app.blade.php dagi inline skript shu nonce'ni oladi.
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
        $dev = $this->viteDevServer();                 // 'http://localhost:5173' yoki null
        $devWs = $dev !== null ? preg_replace('#^http#', 'ws', $dev) : null;

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => array_filter(["'self'", $nonce !== null ? "'nonce-{$nonce}'" : null, $dev]),
            // Vue/Tailwind (dev) <style> teglarini JS orqali qo'shadi — shuning uchun 'unsafe-inline' faqat stillar uchun.
            'style-src' => array_filter(["'self'", "'unsafe-inline'", 'https://fonts.bunny.net', $dev]),
            'font-src' => array_filter(["'self'", 'data:', 'https://fonts.bunny.net', $dev]),
            'img-src' => ["'self'", 'data:', 'blob:'],
            'media-src' => ["'self'"],
            'connect-src' => array_filter(["'self'", $dev, $devWs]),   // OpenAI'ga brauzer emas, server ulanadi
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        return collect($directives)
            ->map(fn (array $sources, string $name) => $name.' '.implode(' ', $sources))
            ->implode('; ');
    }

    /** `npm run dev` ishlayotgan bo'lsa, public/hot faylida Vite manzili turadi. */
    private function viteDevServer(): ?string
    {
        if (! app()->isLocal() || ! Vite::isRunningHot()) {
            return null;
        }

        $url = trim((string) @file_get_contents(Vite::hotFile()));

        return preg_match('#^https?://[^/\s]+$#', $url) ? $url : null;
    }
}
