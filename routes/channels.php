<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('dm.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    return $user->rooms()->where('rooms.id', $roomId)->exists();
});
