<?php

namespace App\Policies;

use App\Models\User;

/**
 * The media library is shared across every course, so creators may add and
 * edit items for their lessons but not remove ones other products may be using.
 */
class MediaItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function view(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isAdmin();
    }
}
