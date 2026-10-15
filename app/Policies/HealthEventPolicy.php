<?php

namespace App\Policies;

use App\Models\HealthEvent;
use App\Models\User;

class HealthEventPolicy
{
    /**
     * Le technicien peut consulter et enregistrer ; le manager tout.
     */
    public function viewAny(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    public function view(User $user, HealthEvent $healthEvent): bool
    {
        return $user->farm_id === $healthEvent->farm_id
            && ($user->isManager() || $user->isTechnician());
    }

    public function create(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    public function update(User $user, HealthEvent $healthEvent): bool
    {
        return $user->farm_id === $healthEvent->farm_id
            && $user->isManager();
    }

    public function delete(User $user, HealthEvent $healthEvent): bool
    {
        return $user->farm_id === $healthEvent->farm_id
            && $user->isManager();
    }
}
