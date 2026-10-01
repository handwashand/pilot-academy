<?php

namespace App\Policies;

use App\Models\Tutorial;
use App\Models\User;

class TutorialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function view(User $user, Tutorial $tutorial): bool
    {
        return $user->canManageTutorial($tutorial);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function update(User $user, Tutorial $tutorial): bool
    {
        return $user->canManageTutorial($tutorial);
    }

    public function delete(User $user, Tutorial $tutorial): bool
    {
        return $user->canManageTutorial($tutorial);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
