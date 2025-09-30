<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Models\Room;

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
}
