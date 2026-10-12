<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function viewAgents(User $user): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdministrator();
    }

    public function updateAvailability(User $user, User $target): bool
    {
        return $user->isAdministrator() || ($user->is($target) && $user->isAgent());
    }
}
