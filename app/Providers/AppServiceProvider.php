<?php

namespace App\Providers;

use App\TryOn\Contracts\TryOnProvider;
use App\TryOn\ProviderFactory;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Default provider for code that does not know the customer's key (TryOnJob uses ProviderFactory directly).
        $this->app->bind(TryOnProvider::class, fn (): TryOnProvider => $this->app->make(ProviderFactory::class)->make(null));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimits();
    }

    /**
     * Try-on rate limits, so a bot or a buggy client cannot burn through a customer's key.
     * Used on routes as ->middleware('throttle:try-on')
     */
    protected function configureRateLimits(): void
    {
        RateLimiter::for('try-on', fn (Request $request) => [
            Limit::perMinute(5)->by('min:'.$request->ip()),
            Limit::perDay(100)->by('day:'.$request->ip()),
        ]);

        // Status polling runs every 2 seconds — 120/minute is plenty.
        RateLimiter::for('try-on-status', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
