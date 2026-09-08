<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Platform administration: partners, accounts, learner records, and which
 * creator owns which product. Creators are deliberately kept out of all of it.
 *
 * Subclassed rather than repeated so that adding a new admin-only resource is
 * one empty class, and so nothing is accidentally left open by omission —
 * Filament asks the policy for every ability, and every one answers here.
 */
abstract class AdminOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ?Model $record = null): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ?Model $record = null): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ?Model $record = null): bool
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
