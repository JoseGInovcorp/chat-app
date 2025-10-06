<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class DirectMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    public function __construct(Message $message)
    {
        // Carrega também o sender para evitar lazy loading no broadcastWith
        $this->message = $message->load('sender:id,name,avatar');
    }

    /**
     * Define os canais de broadcast.
     * Agora envia tanto para o destinatário como para o remetente,
     * mas em canais user.{id}, consistentes com RoomMessageSent.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->message->recipient_id),
            new PrivateChannel('user.' . $this->message->sender_id),
        ];
    }

    /**
     * Dados enviados no broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id'            => $this->message->id,
            'body'          => $this->message->body,
            'created_at'    => $this->message->created_at->format('d/m/Y H:i'),
            'sender_id'     => $this->message->sender_id,
            'recipient_id'  => $this->message->recipient_id,
            'sender_name'   => $this->message->sender->name,
            'sender_avatar' => $this->message->sender->avatar
                ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->message->sender->name),
            'room_id'       => $this->message->room_id,
        ];
    }

    public function broadcastAs(): string
    {
        return 'DirectMessageSent';
    }
}
