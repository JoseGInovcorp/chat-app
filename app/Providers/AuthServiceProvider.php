<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Room;
use App\Policies\RoomPolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Service Provider responsável por registar policies e gates de autorização.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * O mapeamento de modelos para políticas.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Room::class => RoomPolicy::class,
    ];

    /**
     * Registar quaisquer serviços de autenticação/autorização.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Exemplo de Gate adicional (pode expandir conforme necessário):
        // Gate::define('view-dashboard', fn($user) => $user->isAdmin());
    }
}
