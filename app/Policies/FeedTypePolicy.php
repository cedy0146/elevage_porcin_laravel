<?php

namespace App\Policies;

use App\Models\FeedType;
use App\Models\User;

class FeedTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isManager() || $user->isTechnician();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FeedType $feedType): bool
    {
        return $user->farm_id === $feedType->farm_id
            && ($user->isManager() || $user->isTechnician());
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isManager();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FeedType $feedType): bool
    {
        return $user->farm_id === $feedType->farm_id
            && $user->isManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FeedType $feedType): bool
    {
        return $user->farm_id === $feedType->farm_id
            && $user->isManager();
    }
}
