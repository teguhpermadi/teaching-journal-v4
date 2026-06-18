<?php

namespace App\Policies;

use App\Models\LessonPlan;
use App\Models\User;

class LessonPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:LessonPlan');
    }

    public function view(User $user, LessonPlan $lessonPlan): bool
    {
        return $user->can('View:LessonPlan');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:LessonPlan');
    }

    public function update(User $user, LessonPlan $lessonPlan): bool
    {
        return $user->can('Update:LessonPlan');
    }

    public function delete(User $user, LessonPlan $lessonPlan): bool
    {
        return $user->can('Delete:LessonPlan');
    }

    public function restore(User $user, LessonPlan $lessonPlan): bool
    {
        return $user->can('Restore:LessonPlan');
    }

    public function forceDelete(User $user, LessonPlan $lessonPlan): bool
    {
        return $user->can('ForceDelete:LessonPlan');
    }
}
