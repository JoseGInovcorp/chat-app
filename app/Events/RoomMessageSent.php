<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Support\Facades\Log;

class RoomMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    /**
     * Cria uma nova instância do evento.
     */
    public function __construct(Message $message)
    {
        // Carrega o sender e a relação room->users para evitar lazy loading posterior
        $this->message = $message->load('sender:id,name,avatar', 'room.users:id');

        // Log útil para debugging: confirma payload e canais alvo
        try {
            $memberIds = $this->message->room ? $this->message->room->users->pluck('id')->toArray() : [];
            Log::info('RoomMessageSent constructed', [
                'message_id' => $this->message->id,
                'room_id' => $this->message->room_id,
                'member_ids' => $memberIds,
            ]);
        } catch (\Throwable $ex) {
            Log::warning('RoomMessageSent: erro a calcular memberIds', [
                'message_id' => $this->message->id ?? null,
                'error' => $ex->getMessage(),
            ]);
        }
    }

    /**
     * Define os canais de broadcast.
     * Envia para o canal da sala e também para canais privados user.{id} de cada membro
     * para garantir notificações de badge mesmo quando o utilizador não está subscrito ao canal da sala.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('room.' . $this->message->room_id),
        ];

        // Adiciona canais pessoais para cada membro da sala
        if ($this->message->room && $this->message->room->relationLoaded('users')) {
            $memberIds = $this->message->room->users->pluck('id')->toArray();
        } else {
            // fallback: tenta carregar membros sem lançar exceção
            try {
                $memberIds = $this->message->room ? $this->message->room->users()->pluck('users.id')->toArray() : [];
            } catch (\Throwable $ex) {
                $memberIds = [];
                Log::warning('RoomMessageSent: falha ao pluck members', ['error' => $ex->getMessage()]);
            }
        }

        foreach ($memberIds as $id) {
            $channels[] = new PrivateChannel('user.' . $id);
        }

        // Log dos canais concretos (útil para validar em laravel.log)
        try {
            Log::info('RoomMessageSent broadcast channels', [
                'channels' => array_map(fn($c) => (string) $c, $channels),
                'message_id' => $this->message->id,
            ]);
        } catch (\Throwable $ex) {
            // não falhar broadcasting por causa do log
        }

        return $channels;
    }

    /**
     * Dados enviados no broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id'            => $this->message->id,
            'body'          => $this->message->body,
            'created_at'    => $this->message->created_at->format('H:i'),
            'sender_id'     => $this->message->sender_id,
            'sender_name'   => $this->message->sender->name ?? null,
            'sender_avatar' => $this->message->sender->avatar
                ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->message->sender->name ?? ''),
            'room_id'       => $this->message->room_id,
        ];
    }

    public function broadcastAs()
    {
        return 'RoomMessageSent';
    }
}
