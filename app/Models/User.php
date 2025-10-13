<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Modelo Eloquent que representa utilizadores autenticados.
 * Inclui autenticação, notificações e relações com salas e mensagens.
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'status',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    // 🔗 Relações
    public function rooms()
    {
        return $this->belongsToMany(Room::class)
            ->withTimestamps()
            ->withPivot(['invited_by', 'joined_at']);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    // 🔑 Helpers
    public function isAdmin(): bool
    {
        return $this->is_admin || $this->role === 'admin';
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->name);
    }
}
