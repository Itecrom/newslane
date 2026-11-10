<?php

namespace App\Providers;

use App\Models\AdsenseSetting;
use App\Models\Category;
use App\Services\WeatherForecastService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set(config('app.timezone', 'Africa/Blantyre'));

        RateLimiter::for('login', fn ($request) => Limit::perMinute(6)->by($request->ip()));

        View::composer('layouts.app', function ($view): void {
            $view->with([
                'navCategories' => Category::where('in_menu', true)->orderBy('position')->orderBy('name')->get(),
                'weather' => app(WeatherForecastService::class)->forTopbar(),
                'adsenseEnabled' => AdsenseSetting::query()->value('enabled') ?? config('services.adsense.enabled'),
            ]);
        });
    }
}
