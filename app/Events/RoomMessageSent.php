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

    public function __construct(Message $message)
    {
        // Carrega sender e membros da sala
        $this->message = $message->load('sender:id,name,avatar', 'room.users:id');

        try {
            $memberIds = $this->message->room?->users->pluck('id')->toArray() ?? [];
            Log::info('RoomMessageSent constructed', [
                'message_id' => $this->message->id,
                'room_id'    => $this->message->room_id,
                'member_ids' => $memberIds,
            ]);
        } catch (\Throwable $ex) {
            Log::warning('RoomMessageSent: erro a calcular memberIds', [
                'message_id' => $this->message->id ?? null,
                'error'      => $ex->getMessage(),
            ]);
        }
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('room.' . $this->message->room_id)];

        foreach ($this->message->room->users as $user) {
            if ($user->id !== $this->message->sender_id) {
                $channels[] = new PrivateChannel('user.' . $user->id);
            }
        }

        return $channels;
    }

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

    public function broadcastAs(): string
    {
        return 'RoomMessageSent';
    }
}
