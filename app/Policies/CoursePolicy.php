<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /** Creators reach the course list, but only ever see their own products' rows. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function view(User $user, Course $course): bool
    {
        return $user->canManageCourse($course);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function update(User $user, Course $course): bool
    {
        return $user->canManageCourse($course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->canManageCourse($course);
    }

    /** Bulk delete works across the whole selection, so admins only. */
    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Course order is how the whole academy home page reads — admins only. */
    public function reorder(User $user): bool
    {
        return $user->isAdmin();
    }
}
