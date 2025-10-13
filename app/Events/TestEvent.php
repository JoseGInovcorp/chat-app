<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento auxiliar para testar a infraestrutura de broadcast (Echo/Pusher).
 * Apenas para desenvolvimento ou staging.
 */
class TestEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $message;

    /**
     * Cria um novo evento de teste.
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Canal onde o evento será transmitido.
     * Usa PrivateChannel por consistência e segurança.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('test-channel')];
    }

    /**
     * Dados enviados no broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Nome do evento no frontend (Echo).
     */
    public function broadcastAs(): string
    {
        return 'TestEvent';
    }
}
