<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Room;
use App\Models\Message;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) return;

        $password = Hash::make(env('DEFAULT_SEEDER_PASSWORD', 'password'));

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => $password, 'role' => 'admin', 'status' => 'active']
        );

        $user1 = User::firstOrCreate(
            ['email' => 'maria@example.com'],
            ['name' => 'Maria', 'password' => $password, 'role' => 'user', 'status' => 'active']
        );

        $user2 = User::firstOrCreate(
            ['email' => 'joao@example.com'],
            ['name' => 'João', 'password' => $password, 'role' => 'user', 'status' => 'active']
        );

        $room = Room::firstOrCreate(
            ['name' => 'Sala Geral'],
            ['avatar' => null, 'slug' => Str::slug('Sala Geral') . '-' . Str::random(6)]
        );

        $room->users()->syncWithoutDetaching([
            $admin->id => ['invited_by' => $admin->id, 'joined_at' => now()],
            $user1->id => ['invited_by' => $admin->id, 'joined_at' => now()],
            $user2->id => ['invited_by' => $admin->id, 'joined_at' => now()],
        ]);

        if ($room->messages()->count() === 0) {
            Message::create([
                'sender_id' => $admin->id,
                'room_id' => $room->id,
                'body' => 'Bem-vindos à Sala Geral!',
            ]);

            Message::create([
                'sender_id' => $user1->id,
                'room_id' => $room->id,
                'body' => 'Olá a todos 👋',
            ]);

            Message::create([
                'sender_id' => $user2->id,
                'room_id' => $room->id,
                'body' => 'Bora testar este chat 🚀',
            ]);
        }
    }
}
