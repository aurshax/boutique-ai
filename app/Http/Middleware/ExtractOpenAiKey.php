<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Takes the customer's OpenAI key (BYOK) from the `X-OpenAI-Key` header and removes it from the request at once.
 *
 * Why: with APP_DEBUG=true Laravel's error page shows request headers, and loggers or monitoring
 * tools may record them too. The key only lives in request attributes and is handed to TryOnJob
 * (whose payload is encrypted).
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
