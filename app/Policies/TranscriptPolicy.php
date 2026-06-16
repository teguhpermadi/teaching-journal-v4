<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Transcript;
use Illuminate\Auth\Access\HandlesAuthorization;

class TranscriptPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Transcript');
    }

    public function view(AuthUser $authUser, Transcript $transcript): bool
    {
        return $authUser->can('View:Transcript');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Transcript');
    }

    public function update(AuthUser $authUser, Transcript $transcript): bool
    {
        return $authUser->can('Update:Transcript');
    }

    public function delete(AuthUser $authUser, Transcript $transcript): bool
    {
        return $authUser->can('Delete:Transcript');
    }

    public function restore(AuthUser $authUser, Transcript $transcript): bool
    {
        return $authUser->can('Restore:Transcript');
    }

    public function forceDelete(AuthUser $authUser, Transcript $transcript): bool
    {
        return $authUser->can('ForceDelete:Transcript');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Transcript');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Transcript');
    }

    public function replicate(AuthUser $authUser, Transcript $transcript): bool
    {
        return $authUser->can('Replicate:Transcript');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Transcript');
    }

}