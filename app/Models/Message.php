<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['sender_id', 'room_id', 'recipient_id', 'body'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function scopeDirectBetween($q, $u1, $u2)
    {
        return $q->whereNull('room_id')->where(function ($qq) use ($u1, $u2) {
            $qq->where([['sender_id', $u1], ['recipient_id', $u2]])
                ->orWhere([['sender_id', $u2], ['recipient_id', $u1]]);
        });
    }
}
