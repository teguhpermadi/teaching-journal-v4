<?php

namespace App\Policies;

use App\Models\Signature;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SignaturePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Signature');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Signature $signature): bool
    {
        return $user->can('View:Signature');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create:Signature');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Signature $signature): bool
    {
        return $user->can('Update:Signature');
    }

    /**
     * Determine whether the user can delete the model.
     * User can delete signature if:
     * 1. They have the 'Delete:Signature' permission, AND
     * 2. They are the signer of the signature (signer_id matches user id), OR
     * 3. They are a headmaster and the signature is a headmaster signature
     */
    public function delete(User $user, Signature $signature): bool
    {
        if (!$user->can('Delete:Signature')) {
            return false;
        }

        // User can delete their own signature
        if ($signature->signer_id === $user->id) {
            return true;
        }

        // Headmaster can delete headmaster signatures (even if signed by another headmaster)
        if ($user->hasRole('headmaster') && $signature->signer_role === 'headmaster') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Signature $signature): bool
    {
        return $user->can('Restore:Signature');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Signature $signature): bool
    {
        return $user->can('ForceDelete:Signature');
    }
}

