<?php

namespace App\Policies;

use App\Models\Farrowing;
use App\Models\User;

class FarrowingPolicy
{
    /**
     * Le technicien peut enregistrer les mises bas et sevrages ;
     * le manager tout.
     */
    public function viewAny(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    public function view(User $user, Farrowing $farrowing): bool
    {
        return $user->farm_id === $farrowing->farm_id
            && ($user->isManager() || $user->isTechnician());
    }

    public function create(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    public function update(User $user, Farrowing $farrowing): bool
    {
        return $user->farm_id === $farrowing->farm_id
            && $user->isManager();
    }

    public function delete(User $user, Farrowing $farrowing): bool
    {
        return $user->farm_id === $farrowing->farm_id
            && $user->isManager();
    }
}
