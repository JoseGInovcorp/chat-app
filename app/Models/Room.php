<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['name', 'avatar', 'slug'];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['invited_by', 'joined_at'])->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at'); // ordem cronológica
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
