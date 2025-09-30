<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $message;

    /**
     * Cria um novo evento.
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Canal onde o evento será transmitido.
     */
    public function broadcastOn(): array
    {
        return [new Channel('test-channel')];
    }

    /**
     * Nome do evento no frontend (Echo).
     */
    public function broadcastAs(): string
    {
        return 'test-event';
    }
}
