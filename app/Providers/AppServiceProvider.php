<?php

namespace App\Providers;

use App\TryOn\Contracts\TryOnProvider;
use App\TryOn\Providers\MockProvider;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // TryOnProvider interfeysi → config('tryon.provider') bo'yicha implementatsiya.
        // 'openai' 11-taskda qo'shiladi.
        $this->app->bind(TryOnProvider::class, fn (): TryOnProvider => match (config('tryon.provider')) {
            'mock' => new MockProvider,
            default => throw new InvalidArgumentException('Noma\'lum TRYON_PROVIDER: '.config('tryon.provider')),
        });
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
     * Try-on so'rovlari cheklovi: bot yoki xato kod mijoz kalitidan ko'p pul yechmasligi uchun.
     * Route'da: ->middleware('throttle:try-on')
     */
    protected function configureRateLimits(): void
    {
        RateLimiter::for('try-on', fn (Request $request) => [
            Limit::perMinute(5)->by('min:'.$request->ip()),
            Limit::perDay(100)->by('day:'.$request->ip()),
        ]);

        // Status polling har 2 soniyada — 120/daqiqa yetarli.
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
