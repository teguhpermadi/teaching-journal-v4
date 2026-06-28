<?php

namespace App\Policies;

use App\Models\AcademicCalendar;
use App\Models\User;

class AcademicCalendarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:AcademicCalendar');
    }

    public function view(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can('View:AcademicCalendar');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:AcademicCalendar');
    }

    public function update(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can('Update:AcademicCalendar');
    }

    public function delete(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can('Delete:AcademicCalendar');
    }

    public function restore(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can('Restore:AcademicCalendar');
    }

    public function forceDelete(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->can('ForceDelete:AcademicCalendar');
    }
}
