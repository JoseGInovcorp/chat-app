<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Models\Room;
use Illuminate\Validation\ValidationException;

/**
 * Modelo Eloquent que representa mensagens (diretas ou em salas).
 * Inclui validações, relações e métodos utilitários para leitura/não lidas.
 */
class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'room_id',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Validação: não pode ter room_id e recipient_id ao mesmo tempo.
     */
    protected static function booted()
    {
        static::creating(function ($message) {
            if ($message->room_id && $message->recipient_id) {
                throw ValidationException::withMessages([
                    'message' => ['Mensagem não pode pertencer a sala e DM ao mesmo tempo.'],
                ]);
            }
        });
    }

    // 🔗 Relações
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // 📬 DMs entre dois utilizadores
    public function scopeDirectBetween(Builder $query, int $userA, int $userB): Builder
    {
        return $query->where(function ($q) use ($userA, $userB) {
            $q->where('sender_id', $userA)->where('recipient_id', $userB);
        })->orWhere(function ($q) use ($userA, $userB) {
            $q->where('sender_id', $userB)->where('recipient_id', $userA);
        });
    }

    // 🔴 Contagem de mensagens não lidas recebidas de um utilizador
    public static function unreadFrom(User $sender, User $recipient): int
    {
        return self::where('sender_id', $sender->id)
            ->where('recipient_id', $recipient->id)
            ->whereNull('read_at')
            ->count();
    }

    // ✅ Marcar como lidas todas as mensagens recebidas de um utilizador
    public static function markAsReadFrom(User $sender, User $recipient): void
    {
        self::where('sender_id', $sender->id)
            ->where('recipient_id', $recipient->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    // 🔎 Scope adicional para mensagens não lidas
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
