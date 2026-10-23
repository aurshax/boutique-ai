<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;

/**
 * Laravel's TrustProxies, with the proxy list taken from config('app.trusted_proxies').
 * Reading config at request time (not env() in bootstrap/app.php) keeps it working after `config:cache`.
 */
class TrustConfiguredProxies extends TrustProxies
{
    /** @return array<int, string>|string|null */
    protected function proxies()
    {
        $proxies = config('app.trusted_proxies');

        if (! is_string($proxies) || trim($proxies) === '') {
            return null;
        }

        return trim($proxies) === '*' ? '*' : array_map('trim', explode(',', $proxies));
    }
}
