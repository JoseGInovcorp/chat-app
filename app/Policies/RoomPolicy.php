<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function invite(User $user, Room $room): bool
    {
        return $user->isAdmin();
    }
}
