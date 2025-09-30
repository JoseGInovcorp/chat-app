<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    \Log::info('Broadcast room tentativa', [
        'auth_user_id' => $user->id ?? null,
        'roomId' => $roomId,
        'can_access' => $user ? $user->rooms()->where('rooms.id', $roomId)->exists() : false,
    ]);

    return $user && $user->rooms()->where('rooms.id', $roomId)->exists();
});

Broadcast::channel('dm.{userId}', function ($user, $userId) {
    \Log::info('Broadcast DM tentativa', [
        'auth_user_id' => $user->id ?? null,
        'target_userId' => $userId,
        'can_access' => (int) $user->id === (int) $userId,
    ]);

    return (int) $user->id === (int) $userId;
});
