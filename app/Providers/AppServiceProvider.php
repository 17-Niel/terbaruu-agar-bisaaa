<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        if (app()->environment('remote') || config('sdi.force_https')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('req-limit', function ($request) {
            return Limit::perMinutes(5, 300)  // 300 requests per 5 minutes
                ->by($request->ip())
                ->response(function ($request, array $headers) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Terlalu banyak percobaan. Silakan coba lagi dalam 5 menit.',
                        'retry_after' => $headers['Retry-After'] ?? null,
                    ], 429);
                });
        });
    }
}
