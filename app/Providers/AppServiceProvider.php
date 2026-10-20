<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Batas percobaan (lihat config/bp2tl.php). Dipasang di routes/web.php lewat "throttle:nama".
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(config('bp2tl.throttle.login'))
            ->by(mb_strtolower((string) $request->input('username')) . '|' . $request->ip()));

        RateLimiter::for('ajukan', fn (Request $request) => Limit::perMinute(config('bp2tl.throttle.ajukan'))
            ->by($request->ip()));

        RateLimiter::for('chatbot', fn (Request $request) => Limit::perMinute(config('bp2tl.throttle.chatbot'))
            ->by($request->ip()));

        RateLimiter::for('lacak', fn (Request $request) => Limit::perMinute(config('bp2tl.throttle.lacak'))
            ->by($request->ip()));
    }
}
