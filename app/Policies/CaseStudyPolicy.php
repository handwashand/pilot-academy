<?php

namespace App\Policies;

use App\Models\CaseStudy;
use App\Models\User;

class CaseStudyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function view(User $user, CaseStudy $caseStudy): bool
    {
        return $user->canManageCaseStudy($caseStudy);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function update(User $user, CaseStudy $caseStudy): bool
    {
        return $user->canManageCaseStudy($caseStudy);
    }

    public function delete(User $user, CaseStudy $caseStudy): bool
    {
        return $user->canManageCaseStudy($caseStudy);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
