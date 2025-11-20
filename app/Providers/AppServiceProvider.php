<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
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
        // Declare user observer
        User::observe(UserObserver::class);
        // Force HTTPS in production
        if (config('app.url') == 'https://link-to-your-app.vercel.app') {
            \URL::forceScheme('https');
        }
    }
}
