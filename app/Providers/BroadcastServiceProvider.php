<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider responsável por registar rotas e canais de broadcast.
 * Necessário para eventos em tempo real (Echo, Pusher, Soketi).
 */
class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Rotas de autenticação de broadcast
        Broadcast::routes(['middleware' => ['web', 'auth']]);

        // Definições de canais privados/presence
        require base_path('routes/channels.php');
    }
}
