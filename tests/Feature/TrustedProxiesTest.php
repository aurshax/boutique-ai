<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** TrustConfiguredProxies: X-Forwarded-* is honoured only when config('app.trusted_proxies') allows it. */
class TrustedProxiesTest extends TestCase
{
    private const HEADERS = ['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.7'];

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_proxy-probe', fn (Request $request) => [
            'secure' => $request->isSecure(),
            'ip' => $request->ip(),
        ]);
    }

    public function test_forwarded_headers_are_ignored_by_default(): void
    {
        config(['app.trusted_proxies' => null]);

        $this->getJson('/_proxy-probe', self::HEADERS)
            ->assertJson(['secure' => false, 'ip' => '127.0.0.1']);
    }

    public function test_forwarded_headers_are_trusted_behind_a_load_balancer(): void
    {
        config(['app.trusted_proxies' => '*']);

        $this->getJson('/_proxy-probe', self::HEADERS)
            ->assertJson(['secure' => true, 'ip' => '203.0.113.7']);
    }
}
