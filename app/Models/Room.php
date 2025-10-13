<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent que representa salas de chat.
 * Inclui relações com utilizadores e mensagens, e lógica de contagem de não lidas.
 */
class Room extends Model
{
    protected $fillable = ['name', 'avatar', 'slug'];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['invited_by', 'joined_at', 'last_read_at'])
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /**
     * Usa slug em vez de id para rotas.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Conta mensagens não lidas para um utilizador.
     */
    public function unreadCountFor(User $user): int
    {
        $pivot = $this->users()->where('user_id', $user->id)->first();
        $lastRead = $pivot?->pivot?->last_read_at;

        return $this->messages()
            ->where('created_at', '>', $lastRead ?? now()->subYear())
            ->where('sender_id', '!=', $user->id)
            ->count();
    }
}
