<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Room;
use App\Models\Message;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ChatDemoSeeder extends Seeder
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
            ['email' => 'alice@example.com'],
            ['name' => 'Alice', 'password' => $password, 'role' => 'user', 'status' => 'active']
        );

        $user2 = User::firstOrCreate(
            ['email' => 'bob@example.com'],
            ['name' => 'Bob', 'password' => $password, 'role' => 'user', 'status' => 'active']
        );

        $room1 = Room::firstOrCreate(
            ['name' => 'Sala Geral'],
            ['avatar' => null, 'slug' => Str::slug('Sala Geral') . '-' . Str::random(6)]
        );

        $room2 = Room::firstOrCreate(
            ['name' => 'Projeto X'],
            ['avatar' => null, 'slug' => Str::slug('Projeto X') . '-' . Str::random(6)]
        );

        $room1->users()->syncWithoutDetaching([
            $admin->id => ['invited_by' => $admin->id, 'joined_at' => now()],
            $user1->id => ['invited_by' => $admin->id, 'joined_at' => now()],
            $user2->id => ['invited_by' => $admin->id, 'joined_at' => now()],
        ]);

        $room2->users()->syncWithoutDetaching([
            $admin->id => ['invited_by' => $admin->id, 'joined_at' => now()],
            $user1->id => ['invited_by' => $admin->id, 'joined_at' => now()],
        ]);

        Message::firstOrCreate([
            'sender_id' => $admin->id,
            'room_id'   => $room1->id,
            'body'      => 'Bem-vindos à Sala Geral!',
        ]);

        Message::firstOrCreate([
            'sender_id' => $user1->id,
            'room_id'   => $room1->id,
            'body'      => 'Olá a todos 👋',
        ]);

        Message::firstOrCreate([
            'sender_id' => $user2->id,
            'room_id'   => $room1->id,
            'body'      => 'Bom dia!',
        ]);

        Message::firstOrCreate([
            'sender_id'    => $user1->id,
            'recipient_id' => $user2->id,
            'body'         => 'Olá Bob, já viste o Projeto X?',
        ]);

        Message::firstOrCreate([
            'sender_id'    => $user2->id,
            'recipient_id' => $user1->id,
            'body'         => 'Sim, parece promissor!',
        ]);
    }
}
