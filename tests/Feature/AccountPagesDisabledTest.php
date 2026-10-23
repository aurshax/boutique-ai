<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** The public demo runs with AUTH_ENABLED=false: every account page is a 404, the try-on still works. */
class AccountPagesDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.enabled' => false]);
    }

    /** @return array<string, array{string, string}> */
    public static function accountRoutes(): array
    {
        return [
            'login page' => ['get', '/login'],
            'login submit' => ['post', '/login'],
            'register page' => ['get', '/register'],
            'forgot password' => ['get', '/forgot-password'],
            'two-factor challenge' => ['get', '/two-factor-challenge'],
            'dashboard' => ['get', '/dashboard'],
            'settings' => ['get', '/settings/profile'],
            'passkey endpoints' => ['get', '/.well-known/passkey-endpoints'],
        ];
    }

    #[DataProvider('accountRoutes')]
    public function test_account_routes_return_404(string $method, string $uri): void
    {
        $this->withoutVite();

        $this->{$method}($uri)->assertNotFound();
    }

    public function test_public_pages_still_work(): void
    {
        $this->withoutVite();

        $this->get('/')->assertOk();
        $this->get('/try-on')->assertOk();
    }
}
