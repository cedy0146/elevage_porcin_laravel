<?php

namespace App\Policies;

use App\Models\Breeder;
use App\Models\User;

class BreederPolicy
{
    /**
     * Le manager gère les reproducteurs ; le technicien peut consulter.
     */
    public function viewAny(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    public function view(User $user, Breeder $breeder): bool
    {
        return $user->farm_id === $breeder->farm_id
            && ($user->isManager() || $user->isTechnician());
    }

    public function create(User $user): bool
    {
        return $user->isManager();
    }

    public function update(User $user, Breeder $breeder): bool
    {
        return $user->farm_id === $breeder->farm_id
            && $user->isManager();
    }

    public function delete(User $user, Breeder $breeder): bool
    {
        return $user->farm_id === $breeder->farm_id
            && $user->isManager();
    }
}
