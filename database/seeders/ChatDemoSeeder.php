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
        // Criar utilizadores (sem duplicar)
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        $user1 = User::firstOrCreate(
            ['email' => 'alice@example.com'],
            [
                'name' => 'Alice',
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'bob@example.com'],
            [
                'name' => 'Bob',
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
            ]
        );

        // Criar salas (garantir slug)
        $room1 = Room::firstOrCreate(
            ['name' => 'Sala Geral'],
            [
                'avatar' => null,
                'slug' => Str::slug('Sala Geral') . '-' . Str::random(6),
            ]
        );
        if (!$room1->slug) {
            $room1->slug = Str::slug($room1->name) . '-' . Str::random(6);
            $room1->save();
        }

        $room2 = Room::firstOrCreate(
            ['name' => 'Projeto X'],
            [
                'avatar' => null,
                'slug' => Str::slug('Projeto X') . '-' . Str::random(6),
            ]
        );
        if (!$room2->slug) {
            $room2->slug = Str::slug($room2->name) . '-' . Str::random(6);
            $room2->save();
        }

        // Associar utilizadores às salas
        $room1->users()->syncWithoutDetaching([
            $admin->id => ['invited_by' => $admin->id, 'joined_at' => now()],
            $user1->id => ['invited_by' => $admin->id, 'joined_at' => now()],
            $user2->id => ['invited_by' => $admin->id, 'joined_at' => now()],
        ]);

        $room2->users()->syncWithoutDetaching([
            $admin->id => ['invited_by' => $admin->id, 'joined_at' => now()],
            $user1->id => ['invited_by' => $admin->id, 'joined_at' => now()],
        ]);

        // Mensagens em sala (só se não existirem)
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

        // Mensagens diretas (só se não existirem)
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
