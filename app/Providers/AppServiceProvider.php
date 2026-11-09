<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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

        View::composer('*', function ($view): void {
            $view->with('navCategories', Category::where('in_menu', true)->orderBy('position')->orderBy('name')->get());
        });
    }
}
