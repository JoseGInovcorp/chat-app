<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Room;

/**
 * Service Provider responsável por registar grupos de rotas da aplicação.
 * Inclui web, API e pode ser expandido com bindings e rate limiting.
 */
class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // Rate limiting para APIs
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Route model binding: Room por slug
        Route::model('room', Room::class);

        // Rotas Web
        Route::middleware('web')
            ->group(base_path('routes/web.php'));

        // Rotas API
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        // Exemplo: rotas admin
        // Route::middleware(['web', 'auth', 'can:admin'])
        //     ->prefix('admin')
        //     ->group(base_path('routes/admin.php'));
    }
}
