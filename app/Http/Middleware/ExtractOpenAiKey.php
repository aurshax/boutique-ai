<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mijozning OpenAI kalitini (BYOK) `X-OpenAI-Key` sarlavhasidan oladi va DARHOL so'rovdan o'chiradi.
 *
 * Nima uchun: APP_DEBUG=true da Laravel xato sahifasi so'rov sarlavhalarini ko'rsatadi,
 * log va monitoring vositalari ham sarlavhalarni yozishi mumkin. Kalit faqat
 * request attributes ichida qoladi va TryOnJob ga (shifrlangan) uzatiladi.
 */
class ExtractOpenAiKey
{
    public const HEADER = 'X-OpenAI-Key';

    public const ATTRIBUTE = 'openai_key';

    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->headers->get(self::HEADER, ''));

        $request->headers->remove(self::HEADER);
        $request->server->remove('HTTP_X_OPENAI_KEY');

        $request->attributes->set(self::ATTRIBUTE, $key !== '' ? $key : null);

        return $next($request);
    }
}
