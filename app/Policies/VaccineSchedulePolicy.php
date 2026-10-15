<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VaccineSchedule;

class VaccineSchedulePolicy
{
    /**
     * Les calendriers de vaccination sont gérés par le manager ;
     * le technician peut les consulter.
     */
    public function viewAny(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    public function view(User $user, VaccineSchedule $vaccineSchedule): bool
    {
        return $user->farm_id === $vaccineSchedule->farm_id
            && ($user->isManager() || $user->isTechnician());
    }

    public function create(User $user): bool
    {
        return $user->isManager();
    }

    public function update(User $user, VaccineSchedule $vaccineSchedule): bool
    {
        return $user->farm_id === $vaccineSchedule->farm_id
            && $user->isManager();
    }

    public function delete(User $user, VaccineSchedule $vaccineSchedule): bool
    {
        return $user->farm_id === $vaccineSchedule->farm_id
            && $user->isManager();
    }
}
