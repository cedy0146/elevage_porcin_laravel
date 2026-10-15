<?php

namespace App\Policies;

use App\Models\Farm;
use App\Models\User;

class FarmPolicy
{
    /**
     * L'administrateur supervise les exploitations.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * L'admin voit toutes les fermes ; le responsable voit la sienne.
     */
    public function view(User $user, Farm $farm): bool
    {
        return $user->isAdmin()
            || ($user->isManager() && $user->farm_id === $farm->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Farm $farm): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Farm $farm): bool
    {
        return $user->isAdmin();
    }
}
