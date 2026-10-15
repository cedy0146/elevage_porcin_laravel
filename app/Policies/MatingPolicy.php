<?php

namespace App\Policies;

use App\Models\Mating;
use App\Models\User;

class MatingPolicy
{
    /**
     * Le technicien peut enregistrer les saillies ; le manager tout.
     */
    public function viewAny(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    public function view(User $user, Mating $mating): bool
    {
        return $user->farm_id === $mating->farm_id
            && ($user->isManager() || $user->isTechnician());
    }

    public function create(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    public function update(User $user, Mating $mating): bool
    {
        return $user->farm_id === $mating->farm_id
            && $user->isManager();
    }

    public function delete(User $user, Mating $mating): bool
    {
        return $user->farm_id === $mating->farm_id
            && $user->isManager();
    }
}
