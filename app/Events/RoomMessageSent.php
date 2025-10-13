<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Evento de broadcast para mensagens em salas.
 * Envia dados da mensagem para o canal da sala e para os canais privados
 * de cada membro (incluindo o remetente, para sincronização multi-abas).
 */
class RoomMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    public function __construct(Message $message)
    {
        // Carrega sender e membros da sala para evitar lazy loading
        $this->message = $message->load('sender:id,name,avatar', 'room.users:id');
    }

    /**
     * Define os canais de broadcast.
     * Envia para o canal da sala e para todos os membros, incluindo o remetente.
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('room.' . $this->message->room_id)];

        foreach ($this->message->room->users as $user) {
            $channels[] = new PrivateChannel('user.' . $user->id);
        }

        return $channels;
    }

    /**
     * Dados enviados no broadcast.
     * Usa ISO 8601 para created_at, deixando a formatação para o frontend.
     */
    public function broadcastWith(): array
    {
        return [
            'id'            => $this->message->id,
            'body'          => $this->message->body,
            'created_at'    => $this->message->created_at->toIso8601String(),
            'sender_id'     => $this->message->sender_id,
            'sender_name'   => $this->message->sender->name ?? null,
            // Melhor prática: delegar a lógica do avatar para um accessor no modelo User
            'sender_avatar' => $this->message->sender->avatar
                ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->message->sender->name ?? ''),
            'room_id'       => $this->message->room_id,
        ];
    }

    public function broadcastAs(): string
    {
        return 'RoomMessageSent';
    }
}
