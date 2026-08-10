<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

/**
 * A lesson inherits its permissions from the course it belongs to, so a creator
 * can only touch lessons inside their own products' courses.
 */
class LessonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function view(User $user, Lesson $lesson): bool
    {
        return $user->canManageCourse($lesson->course);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->canManageCourse($lesson->course);
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->canManageCourse($lesson->course);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isAdmin() || $user->isCreator();
    }
}
