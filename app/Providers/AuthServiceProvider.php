<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Room;
use App\Policies\RoomPolicy;

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
        // Aqui podes definir Gates adicionais se precisares
    }
}
