<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\User;
use App\Models\Message;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.navigation', function ($view) {
            $user = auth()->user();

            if ($user) {
                // ✅ Salas com pivot carregado corretamente
                $rooms = $user->rooms()
                    ->withPivot('last_read_at')
                    ->orderBy('name')
                    ->get()
                    ->map(function ($room) use ($user) {
                        $lastRead = $room->pivot?->last_read_at;

                        $room->unread_count = $room->messages()
                            ->where('created_at', '>', $lastRead ?? now()->subYear())
                            ->where('sender_id', '!=', $user->id)
                            ->count();

                        return $room;
                    });

                // ✅ Diretas com contagem de mensagens não lidas
                $directContacts = User::where('status', 'active')
                    ->where('id', '!=', $user->id)
                    ->orderBy('name')
                    ->get()
                    ->map(function ($contact) use ($user) {
                        $contact->unread_count = Message::unreadFrom($contact, $user);
                        return $contact;
                    });
            } else {
                $rooms = collect();
                $directContacts = collect();
            }

            $view->with(compact('rooms', 'directContacts'));
        });
    }
}
