<?php

namespace App\Providers;

use App\TryOn\Contracts\TryOnProvider;
use App\TryOn\Providers\MockProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
