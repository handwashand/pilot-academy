<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Webinar;

class WebinarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function view(User $user, Webinar $webinar): bool
    {
        return $user->canManageWebinar($webinar);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function update(User $user, Webinar $webinar): bool
    {
        return $user->canManageWebinar($webinar);
    }

    public function delete(User $user, Webinar $webinar): bool
    {
        return $user->canManageWebinar($webinar);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
