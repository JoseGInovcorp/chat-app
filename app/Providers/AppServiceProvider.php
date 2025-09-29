<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\User;

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
        // Partilhar dados com a sidebar (layouts/navigation.blade.php)
        View::composer('layouts.navigation', function ($view) {
            $user = auth()->user();

            if ($user) {
                // Salas em que o utilizador participa
                $rooms = $user->rooms()->orderBy('name')->get();

                // Contactos diretos (todos os outros utilizadores ativos)
                $directContacts = User::where('status', 'active')
                    ->where('id', '!=', $user->id)
                    ->orderBy('name')
                    ->get();
            } else {
                $rooms = collect();
                $directContacts = collect();
            }

            $view->with(compact('rooms', 'directContacts'));
        });
    }
}
