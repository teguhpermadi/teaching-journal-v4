<?php

namespace App\Policies;

use App\Models\Transcript;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TranscriptPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Transcript');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transcript $transcript): bool
    {
        return $user->can('View:Transcript');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create:Transcript');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Transcript $transcript): bool
    {
        return $user->can('Update:Transcript');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transcript $transcript): bool
    {
        return $user->can('Delete:Transcript');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Transcript $transcript): bool
    {
        return $user->can('Restore:Transcript');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Transcript $transcript): bool
    {
        return $user->can('ForceDelete:Transcript');
    }
}
