<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\User;
use App\Models\Message;

/**
 * Service Provider principal da aplicação.
 * Regista view composers e bindings globais.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ⚠️ Melhor prática: mover para um ViewServiceProvider dedicado
        View::composer('layouts.navigation', function ($view) {
            $user = auth()->user();

            $rooms = collect();
            $directContacts = collect();

            if ($user) {
                $rooms = $user->rooms()
                    ->withPivot('last_read_at')
                    ->orderBy('name')
                    ->get()
                    ->map(fn($room) => tap($room, function ($room) use ($user) {
                        $room->unread_count = $room->unreadCountFor($user);
                    }));

                $directContacts = User::where('status', 'active')
                    ->where('id', '!=', $user->id)
                    ->orderBy('name')
                    ->get()
                    ->map(fn($contact) => tap($contact, function ($contact) use ($user) {
                        $contact->unread_count = Message::unreadFrom($contact, $user);
                    }));
            }

            $view->with(compact('rooms', 'directContacts'));
        });
    }
}
