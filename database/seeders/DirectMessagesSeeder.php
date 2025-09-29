<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Hash;

class DirectMessagesSeeder extends Seeder
{
    public function run(): void
    {
        // Garante que os utilizadores existem
        $user1 = User::firstOrCreate(
            ['email' => 'maria@example.com'],
            [
                'name' => 'Maria',
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'joao@example.com'],
            [
                'name' => 'João',
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
            ]
        );

        // Só cria as mensagens se ainda não existirem
        if (!Message::where('sender_id', $user1->id)->where('recipient_id', $user2->id)->exists()) {
            Message::create([
                'sender_id'    => $user1->id,
                'recipient_id' => $user2->id,
                'body'         => 'Olá João, tudo bem?',
            ]);
        }

        if (!Message::where('sender_id', $user2->id)->where('recipient_id', $user1->id)->exists()) {
            Message::create([
                'sender_id'    => $user2->id,
                'recipient_id' => $user1->id,
                'body'         => 'Tudo ótimo, Maria! E contigo?',
            ]);
        }
    }
}
