<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

/**
 * Policy responsável por gerir permissões relacionadas a salas.
 */
class RoomPolicy
{
    /**
     * Apenas administradores podem criar salas.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Apenas administradores ou membros da sala podem convidar.
     */
    public function invite(User $user, Room $room): bool
    {
        return $user->isAdmin();
    }

    /**
     * Um utilizador pode ver a sala se for admin ou membro.
     */
    public function view(User $user, Room $room): bool
    {
        return $user->isAdmin() || $room->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Apenas administradores ou criadores podem atualizar a sala.
     */
    public function update(User $user, Room $room): bool
    {
        return $user->isAdmin() || $room->users()->wherePivot('invited_by', $user->id)->exists();
    }

    /**
     * Apenas administradores podem eliminar salas.
     */
    public function delete(User $user, Room $room): bool
    {
        return $user->isAdmin();
    }
}
