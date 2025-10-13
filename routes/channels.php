<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Room;

Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    return $user && $user->rooms()->where('rooms.id', $roomId)->exists();
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
