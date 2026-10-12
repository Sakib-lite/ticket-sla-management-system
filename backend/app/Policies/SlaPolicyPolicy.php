<?php

namespace App\Policies;

use App\Models\SlaPolicy;
use App\Models\User;

class SlaPolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, SlaPolicy $policy): bool
    {
        return $user->isAdministrator();
    }
}
