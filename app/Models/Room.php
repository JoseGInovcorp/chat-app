<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['name', 'avatar', 'slug'];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['invited_by', 'joined_at'])
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    // ⚠️ Se quiseres usar slug, mantém este método.
    // Mas certifica-te que TODAS as salas têm slug preenchido.
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function unreadCountFor(User $user)
    {
        $pivot = $this->users()->where('user_id', $user->id)->first();
        $lastRead = $pivot?->pivot?->last_read_at;

        return $this->messages()
            ->where('created_at', '>', $lastRead ?? now()->subYear())
            ->where('sender_id', '!=', $user->id)
            ->count();
    }
}
