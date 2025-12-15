<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // 👈 1. IMPORT THIS

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 👈 2. ADD THIS LOGIC
        // This checks if the incoming request host contains 'ngrok'
        if (str_contains(request()->getHost(), 'ngrok-free.dev')) {
            URL::forceScheme('https');
        }
    }
}
