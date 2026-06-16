<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AcademicEvent;
use Illuminate\Auth\Access\HandlesAuthorization;

class AcademicEventPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AcademicEvent');
    }

    public function view(AuthUser $authUser, AcademicEvent $academicEvent): bool
    {
        return $authUser->can('View:AcademicEvent');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AcademicEvent');
    }

    public function update(AuthUser $authUser, AcademicEvent $academicEvent): bool
    {
        return $authUser->can('Update:AcademicEvent');
    }

    public function delete(AuthUser $authUser, AcademicEvent $academicEvent): bool
    {
        return $authUser->can('Delete:AcademicEvent');
    }

    public function restore(AuthUser $authUser, AcademicEvent $academicEvent): bool
    {
        return $authUser->can('Restore:AcademicEvent');
    }

    public function forceDelete(AuthUser $authUser, AcademicEvent $academicEvent): bool
    {
        return $authUser->can('ForceDelete:AcademicEvent');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AcademicEvent');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AcademicEvent');
    }

    public function replicate(AuthUser $authUser, AcademicEvent $academicEvent): bool
    {
        return $authUser->can('Replicate:AcademicEvent');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AcademicEvent');
    }

}