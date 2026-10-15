<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Seul l'administrateur gère les comptes.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * L'admin voit tous les comptes ; le responsable voit ceux de sa ferme ; chacun se voit soi-même.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->id === $model->id) {
            return true;
        }

        return $user->isManager()
            && $user->farm_id !== null
            && $user->farm_id === $model->farm_id;
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
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin();
    }
}
